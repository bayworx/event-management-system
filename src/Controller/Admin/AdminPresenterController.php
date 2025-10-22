<?php

namespace App\Controller\Admin;

use App\Entity\Presenter;
use App\Form\PresenterType;
use App\Repository\PresenterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/presenters')]
#[IsGranted('ROLE_ADMIN')]
class AdminPresenterController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PresenterRepository $presenterRepository
    ) {
    }

    #[Route('/', name: 'admin_presenter_index', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('search');
        
        $queryBuilder = $this->presenterRepository->createQueryBuilder('p');
        
        if ($search) {
            $queryBuilder
                ->where('p.name LIKE :search')
                ->orWhere('p.email LIKE :search')
                ->orWhere('p.company LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        $queryBuilder->orderBy('p.name', 'ASC');

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('admin/presenter/index.html.twig', [
            'pagination' => $pagination,
            'search' => $search,
        ]);
    }

    #[Route('/new', name: 'admin_presenter_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $presenter = new Presenter();
        $form = $this->createForm(PresenterType::class, $presenter);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->entityManager->persist($presenter);
                $this->entityManager->flush();

                $this->addFlash('success', 'Presenter created successfully.');
                return $this->redirectToRoute('admin_presenter_show', ['id' => $presenter->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error creating presenter: ' . $e->getMessage());
            }
        }

        return $this->render('admin/presenter/new.html.twig', [
            'presenter' => $presenter,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_presenter_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $presenter = $this->presenterRepository->find($id);
        if (!$presenter) {
            throw $this->createNotFoundException('Presenter not found');
        }

        return $this->render('admin/presenter/show.html.twig', [
            'presenter' => $presenter,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_presenter_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, int $id): Response
    {
        $presenter = $this->presenterRepository->find($id);
        if (!$presenter) {
            throw $this->createNotFoundException('Presenter not found');
        }

        $form = $this->createForm(PresenterType::class, $presenter);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $presenter->setUpdatedAt(new \DateTime());
                $this->entityManager->flush();

                $this->addFlash('success', 'Presenter updated successfully.');
                return $this->redirectToRoute('admin_presenter_show', ['id' => $presenter->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error updating presenter: ' . $e->getMessage());
            }
        }

        return $this->render('admin/presenter/edit.html.twig', [
            'presenter' => $presenter,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_presenter_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, int $id): Response
    {
        $presenter = $this->presenterRepository->find($id);
        if (!$presenter) {
            throw $this->createNotFoundException('Presenter not found');
        }

        if ($this->isCsrfTokenValid('delete-' . $presenter->getId(), $request->request->get('_token'))) {
            // Check if presenter is assigned to any events
            if (count($presenter->getEventPresenters()) > 0) {
                $this->addFlash('error', 'Cannot delete presenter who is assigned to events. Please remove them from events first.');
                return $this->redirectToRoute('admin_presenter_show', ['id' => $presenter->getId()]);
            }

            try {
                $this->entityManager->remove($presenter);
                $this->entityManager->flush();

                $this->addFlash('success', 'Presenter deleted successfully.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error deleting presenter: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_presenter_index');
    }

    #[Route('/api/search', name: 'admin_presenter_search_api', methods: ['GET'])]
    public function searchApi(Request $request): Response
    {
        $term = $request->query->get('term', '');
        
        if (strlen($term) < 2) {
            return $this->json([]);
        }

        $presenters = $this->presenterRepository->findBySearchTerm($term);
        
        $results = array_map(function (Presenter $presenter) {
            return [
                'id' => $presenter->getId(),
                'name' => $presenter->getName(),
                'title' => $presenter->getTitle(),
                'company' => $presenter->getCompany(),
                'fullName' => $presenter->getFullName(),
            ];
        }, $presenters);

        return $this->json($results);
    }
}