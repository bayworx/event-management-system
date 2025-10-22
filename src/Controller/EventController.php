<?php

namespace App\Controller;

use App\Entity\Attendee;
use App\Entity\Event;
use App\Repository\EventRepository;
use App\Repository\EventFileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Vich\UploaderBundle\Handler\DownloadHandler;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/event')]
class EventController extends AbstractController
{
    #[Route('s', name: 'events_list')]
    public function list(
        Request $request,
        EventRepository $eventRepository,
        PaginatorInterface $paginator
    ): Response {
        $query = $request->query->get('q');
        $filter = $request->query->get('filter', 'upcoming');

        switch ($filter) {
            case 'past':
                $events = $eventRepository->findPast();
                break;
            case 'all':
                $events = $eventRepository->findActive();
                break;
            case 'upcoming':
            default:
                $events = $eventRepository->findUpcoming();
                break;
        }

        if ($query) {
            $events = $eventRepository->search($query);
        }

        $pagination = $paginator->paginate(
            $events,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('event/list.html.twig', [
            'pagination' => $pagination,
            'query' => $query,
            'filter' => $filter,
        ]);
    }

    #[Route('s/calendar', name: 'events_calendar')]
    public function calendar(
        Request $request,
        EventRepository $eventRepository
    ): Response {
        $year = $request->query->getInt('year', (int)date('Y'));
        $month = $request->query->getInt('month', (int)date('n'));
        
        // Validate year and month
        if ($year < 2020 || $year > 2030) {
            $year = (int)date('Y');
        }
        if ($month < 1 || $month > 12) {
            $month = (int)date('n');
        }
        
        // Get first and last day of the month
        $firstDay = new \DateTime(sprintf('%d-%02d-01', $year, $month));
        $lastDay = clone $firstDay;
        $lastDay->modify('last day of this month');
        
        // Get events for this month
        $events = $eventRepository->findByDateRange($firstDay, $lastDay);
        
        // Group events by date
        $eventsByDate = [];
        foreach ($events as $event) {
            $dateKey = $event->getStartDate()->format('Y-m-d');
            if (!isset($eventsByDate[$dateKey])) {
                $eventsByDate[$dateKey] = [];
            }
            $eventsByDate[$dateKey][] = $event;
        }
        
        // Calculate calendar structure
        $calendar = $this->buildCalendarStructure($year, $month, $eventsByDate);
        
        return $this->render('event/calendar.html.twig', [
            'calendar' => $calendar,
            'currentYear' => $year,
            'currentMonth' => $month,
            'eventsByDate' => $eventsByDate,
            'monthName' => $firstDay->format('F'),
            'prevMonth' => $this->getPreviousMonth($year, $month),
            'nextMonth' => $this->getNextMonth($year, $month),
        ]);
    }

    #[Route('/{slug}', name: 'event_show')]
    public function show(
        string $slug,
        EventRepository $eventRepository,
        EventFileRepository $eventFileRepository
    ): Response {
        $event = $eventRepository->findOneBySlug($slug);

        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        // If event is deactivated, show a special message
        if (!$event->isActive()) {
            return $this->render('event/show.html.twig', [
                'event' => $event,
                'files' => [],
                'attendee_count' => 0,
                'user_is_attendee' => false,
                'event_deactivated' => true,
            ]);
        }

        $files = $eventFileRepository->findActiveByEvent($event);
        $attendeeCount = $event->getAttendeesCount();

        $userIsAttendee = false;
        if ($this->getUser() instanceof Attendee) {
            $userIsAttendee = $this->getUser()->getEvent() === $event;
        }

        return $this->render('event/show.html.twig', [
            'event' => $event,
            'files' => $files,
            'attendee_count' => $attendeeCount,
            'user_is_attendee' => $userIsAttendee,
            'event_deactivated' => false,
        ]);
    }

    #[Route('/{slug}/register', name: 'event_register', methods: ['GET', 'POST'])]
    public function register(
        string $slug,
        Request $request,
        EventRepository $eventRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        MailerInterface $mailer
    ): Response {
        $event = $eventRepository->findOneBySlug($slug);

        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        if (!$event->isActive()) {
            $this->addFlash('error', 'This event is no longer available for registration.');
            return $this->redirectToRoute('event_show', ['slug' => $slug]);
        }

        if (!$event->canAcceptMoreAttendees()) {
            $this->addFlash('error', 'This event is full and can no longer accept registrations.');
            return $this->redirectToRoute('event_show', ['slug' => $slug]);
        }

        $attendee = new Attendee();
        $attendee->setEvent($event);

        if ($request->isMethod('POST')) {
            $attendee->setName($request->request->get('name'));
            $attendee->setEmail($request->request->get('email'));
            $attendee->setPhone($request->request->get('phone'));
            $attendee->setOrganization($request->request->get('organization'));
            $attendee->setJobTitle($request->request->get('job_title'));
            $attendee->setNotes($request->request->get('notes'));

            $errors = $validator->validate($attendee);

            if (count($errors) === 0) {
                // Check if email is already registered for this event
                $existingAttendee = $entityManager->getRepository(Attendee::class)
                    ->findOneBy(['email' => $attendee->getEmail(), 'event' => $event]);

                if ($existingAttendee) {
                    $this->addFlash('warning', 'This email is already registered for this event. A verification link will be sent to your email.');
                    
                    // Resend verification email
                    if (!$existingAttendee->getEmailVerificationToken()) {
                        $existingAttendee->generateEmailVerificationToken();
                        $entityManager->persist($existingAttendee);
                        $entityManager->flush();
                    }

                    $this->sendVerificationEmail($existingAttendee, $mailer);
                    return $this->redirectToRoute('event_show', ['slug' => $slug]);
                }

                $entityManager->persist($attendee);
                $entityManager->flush();

                // Send verification email
                $this->sendVerificationEmail($attendee, $mailer);

                $this->addFlash('success', 'Registration successful! Please check your email for a verification link to access event materials.');
                return $this->redirectToRoute('event_show', ['slug' => $slug]);
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            }
        }

        return $this->render('event/register.html.twig', [
            'event' => $event,
            'attendee' => $attendee,
        ]);
    }

    #[Route('/{slug}/file/{id}/download', name: 'event_file_download')]
    public function downloadFile(
        string $slug,
        int $id,
        EventRepository $eventRepository,
        EventFileRepository $eventFileRepository,
        EntityManagerInterface $entityManager,
        DownloadHandler $downloadHandler
    ): Response {
        $event = $eventRepository->findOneBySlug($slug);
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        $file = $eventFileRepository->find($id);
        if (!$file || $file->getEvent() !== $event || !$file->isActive()) {
            throw $this->createNotFoundException('File not found');
        }

        // Check if user is authenticated attendee for this event or admin
        $user = $this->getUser();
        $canDownload = false;

        if ($this->isGranted('ROLE_ADMIN')) {
            $canDownload = true;
        } elseif ($user instanceof Attendee && $user->getEvent() === $event && $user->isVerified()) {
            $canDownload = true;
        }

        if (!$canDownload) {
            $this->addFlash('error', 'You must be registered and verified for this event to download files.');
            return $this->redirectToRoute('event_show', ['slug' => $slug]);
        }

        // Increment download count
        $file->incrementDownloadCount();
        $entityManager->persist($file);
        $entityManager->flush();

        // Use VichUploader to handle the file download
        return $downloadHandler->downloadObject($file, 'file', null, $file->getOriginalName());
    }

    private function sendVerificationEmail(Attendee $attendee, MailerInterface $mailer): void
    {
        $verifyUrl = $this->generateUrl('attendee_email_verify', [
            'slug' => $attendee->getEvent()->getSlug(),
            'token' => $attendee->getEmailVerificationToken()
        ], true);

        $email = (new Email())
            ->from('noreply@example.com')
            ->to($attendee->getEmail())
            ->subject('Verify your registration for ' . $attendee->getEvent()->getTitle())
            ->html($this->renderView('emails/verification.html.twig', [
                'attendee' => $attendee,
                'event' => $attendee->getEvent(),
                'verify_url' => $verifyUrl
            ]));

        $mailer->send($email);
    }

    private function buildCalendarStructure(int $year, int $month, array $eventsByDate): array
    {
        $firstDay = new \DateTime(sprintf('%d-%02d-01', $year, $month));
        $lastDay = clone $firstDay;
        $lastDay->modify('last day of this month');
        
        // Get the first day of the week (Sunday = 0)
        $firstDayOfWeek = (int)$firstDay->format('w');
        
        // Calculate the start date (may be from previous month)
        $startDate = clone $firstDay;
        if ($firstDayOfWeek > 0) {
            $startDate->modify('-' . $firstDayOfWeek . ' days');
        }
        
        $calendar = [];
        $currentDate = clone $startDate;
        
        // Build 6 weeks (42 days) to ensure full calendar grid
        for ($week = 0; $week < 6; $week++) {
            $calendar[$week] = [];
            for ($day = 0; $day < 7; $day++) {
                $dateKey = $currentDate->format('Y-m-d');
                $isCurrentMonth = $currentDate->format('m') == $month;
                $isToday = $currentDate->format('Y-m-d') === date('Y-m-d');
                
                $calendar[$week][$day] = [
                    'date' => clone $currentDate,
                    'day' => (int)$currentDate->format('d'),
                    'isCurrentMonth' => $isCurrentMonth,
                    'isToday' => $isToday,
                    'events' => isset($eventsByDate[$dateKey]) ? $eventsByDate[$dateKey] : [],
                ];
                
                $currentDate->modify('+1 day');
            }
        }
        
        return $calendar;
    }

    private function getPreviousMonth(int $year, int $month): array
    {
        $prevMonth = $month - 1;
        $prevYear = $year;
        
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }
        
        return ['year' => $prevYear, 'month' => $prevMonth];
    }

    private function getNextMonth(int $year, int $month): array
    {
        $nextMonth = $month + 1;
        $nextYear = $year;
        
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }
        
        return ['year' => $nextYear, 'month' => $nextMonth];
    }
}