<?php

namespace App\Controller;

use App\Entity\Attendee;
use App\Repository\AttendeeRepository;
use App\Service\ApplicationLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public function __construct(
        private ApplicationLogger $appLogger
    ) {
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        
        if ($error) {
            $this->appLogger->logSecurityEvent(
                'login_failed',
                null,
                $request,
                [
                    'username' => $lastUsername,
                    'error' => $error->getMessage(),
                ]
            );
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/event/{slug}/verify/{token}', name: 'attendee_email_verify')]
    public function verifyEmail(
        string $slug,
        string $token,
        AttendeeRepository $attendeeRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        $attendee = $attendeeRepository->findOneByEmailVerificationToken($token);

        if (!$attendee) {
            $this->appLogger->logSecurityEvent(
                'email_verification_failed',
                null,
                $request,
                ['token' => $token, 'slug' => $slug, 'reason' => 'invalid_token']
            );
            $this->addFlash('error', 'Invalid or expired verification token.');
            return $this->redirectToRoute('event_show', ['slug' => $slug]);
        }

        if ($attendee->getEvent()->getSlug() !== $slug) {
            $this->appLogger->logSecurityEvent(
                'email_verification_failed',
                $attendee,
                $request,
                ['token' => $token, 'slug' => $slug, 'reason' => 'slug_mismatch']
            );
            $this->addFlash('error', 'Invalid verification link.');
            return $this->redirectToRoute('event_show', ['slug' => $slug]);
        }

        $attendee->setIsVerified(true);
        $attendee->setEmailVerificationToken(null);
        
        $entityManager->persist($attendee);
        $entityManager->flush();
        
        $this->appLogger->logSecurityEvent(
            'email_verified',
            $attendee,
            $request,
            ['event_id' => $attendee->getEvent()->getId(), 'event_slug' => $slug]
        );

        $this->addFlash('success', 'Your email has been verified successfully! You can now access event materials.');

        return $this->redirectToRoute('event_show', ['slug' => $slug]);
    }

    #[Route('/event/{slug}/login-link', name: 'attendee_login_link', methods: ['POST'])]
    public function sendLoginLink(
        string $slug,
        Request $request,
        AttendeeRepository $attendeeRepository,
        MailerInterface $mailer,
        EntityManagerInterface $entityManager
    ): Response {
        $email = $request->request->get('email');
        
        if (!$email) {
            $this->appLogger->logSecurityEvent(
                'login_link_request_failed',
                null,
                $request,
                ['slug' => $slug, 'reason' => 'missing_email']
            );
            $this->addFlash('error', 'Please provide an email address.');
            return $this->redirectToRoute('event_show', ['slug' => $slug]);
        }

        $attendees = $attendeeRepository->createQueryBuilder('a')
            ->leftJoin('a.event', 'e')
            ->where('a.email = :email')
            ->andWhere('e.slug = :slug')
            ->setParameter('email', $email)
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getResult();

        if (empty($attendees)) {
            // Don't reveal if email exists or not for security
            $this->appLogger->logSecurityEvent(
                'login_link_requested',
                null,
                $request,
                ['slug' => $slug, 'email' => $email, 'result' => 'email_not_found']
            );
            $this->addFlash('info', 'If this email is registered for the event, you will receive a login link shortly.');
            return $this->redirectToRoute('event_show', ['slug' => $slug]);
        }

        foreach ($attendees as $attendee) {
            // Generate new login token if needed
            if (!$attendee->getEmailVerificationToken()) {
                $attendee->generateEmailVerificationToken();
                $entityManager->persist($attendee);
            }

            // Send login link email
            $loginUrl = $this->generateUrl('attendee_email_verify', [
                'slug' => $slug,
                'token' => $attendee->getEmailVerificationToken()
            ], true);

            $emailMessage = (new Email())
                ->from('noreply@example.com')
                ->to($attendee->getEmail())
                ->subject('Login to ' . $attendee->getEvent()->getTitle())
                ->html($this->renderView('emails/login_link.html.twig', [
                    'attendee' => $attendee,
                    'event' => $attendee->getEvent(),
                    'login_url' => $loginUrl
                ]));

            $mailer->send($emailMessage);
            
            $this->appLogger->logSecurityEvent(
                'login_link_sent',
                $attendee,
                $request,
                ['event_id' => $attendee->getEvent()->getId(), 'event_slug' => $slug]
            );
        }

        $entityManager->flush();

        $this->addFlash('info', 'If this email is registered for the event, you will receive a login link shortly.');
        return $this->redirectToRoute('event_show', ['slug' => $slug]);
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        $user = $this->getUser();
        
        if ($user instanceof \App\Entity\Administrator) {
            return $this->redirectToRoute('admin_dashboard');
        }
        
        if ($user instanceof \App\Entity\Attendee) {
            return $this->redirectToRoute('attendee_dashboard');
        }

        return $this->redirectToRoute('app_login');
    }
}