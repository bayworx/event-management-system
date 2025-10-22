<?php

namespace App\Controller\Admin;

use App\Entity\Administrator;
use App\Form\AdminUserType;
use App\Form\AdminUserFilterType;
use App\Repository\AdministratorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class AdminUserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AdministratorRepository $administratorRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('/', name: 'admin_user_index', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $filterForm = $this->createForm(AdminUserFilterType::class);
        $filterForm->handleRequest($request);

        $queryBuilder = $this->administratorRepository->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC');

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $filters = $filterForm->getData();
            
            if (!empty($filters['search'])) {
                $queryBuilder
                    ->andWhere('a.name LIKE :search OR a.email LIKE :search OR a.department LIKE :search')
                    ->setParameter('search', '%' . $filters['search'] . '%');
            }

            if (isset($filters['isActive'])) {
                $queryBuilder
                    ->andWhere('a.isActive = :isActive')
                    ->setParameter('isActive', $filters['isActive']);
            }

            if (isset($filters['isSuperAdmin'])) {
                $queryBuilder
                    ->andWhere('a.isSuperAdmin = :isSuperAdmin')
                    ->setParameter('isSuperAdmin', $filters['isSuperAdmin']);
            }

            if (!empty($filters['department'])) {
                $queryBuilder
                    ->andWhere('a.department = :department')
                    ->setParameter('department', $filters['department']);
            }
        }

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/user/index.html.twig', [
            'pagination' => $pagination,
            'filter_form' => $filterForm->createView(),
        ]);
    }

    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $administrator = new Administrator();
        $form = $this->createForm(AdminUserType::class, $administrator, ['is_new' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password
            if ($plainPassword = $form->get('plainPassword')->getData()) {
                $hashedPassword = $this->passwordHasher->hashPassword($administrator, $plainPassword);
                $administrator->setPassword($hashedPassword);
            }

            $this->entityManager->persist($administrator);
            $this->entityManager->flush();

            $this->addFlash('success', 'Administrator created successfully.');
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/new.html.twig', [
            'administrator' => $administrator,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_user_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Administrator $administrator): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'administrator' => $administrator,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Administrator $administrator): Response
    {
        $currentUser = $this->getUser();
        
        // Prevent editing your own super admin status
        $canEditSuperAdmin = $administrator->getId() !== $currentUser->getId();

        $form = $this->createForm(AdminUserType::class, $administrator, [
            'is_new' => false,
            'can_edit_super_admin' => $canEditSuperAdmin
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password if changed
            if ($plainPassword = $form->get('plainPassword')->getData()) {
                $hashedPassword = $this->passwordHasher->hashPassword($administrator, $plainPassword);
                $administrator->setPassword($hashedPassword);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Administrator updated successfully.');
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/edit.html.twig', [
            'administrator' => $administrator,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/toggle-status', name: 'admin_user_toggle_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleStatus(Request $request, Administrator $administrator): Response
    {
        $currentUser = $this->getUser();
        
        // Prevent disabling your own account
        if ($administrator->getId() === $currentUser->getId()) {
            $this->addFlash('error', 'You cannot disable your own account.');
            return $this->redirectToRoute('admin_user_index');
        }

        if ($this->isCsrfTokenValid('toggle-status-' . $administrator->getId(), $request->request->get('_token'))) {
            $administrator->setIsActive(!$administrator->isActive());
            $this->entityManager->flush();

            $status = $administrator->isActive() ? 'activated' : 'deactivated';
            $this->addFlash('success', "Administrator {$status} successfully.");
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_user_index');
    }

    #[Route('/{id}/delete', name: 'admin_user_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Administrator $administrator): Response
    {
        $currentUser = $this->getUser();
        
        // Prevent deleting your own account
        if ($administrator->getId() === $currentUser->getId()) {
            $this->addFlash('error', 'You cannot delete your own account.');
            return $this->redirectToRoute('admin_user_index');
        }

        // Check if this is the last super admin
        if ($administrator->isSuperAdmin()) {
            $superAdminCount = $this->administratorRepository->countSuperAdmins();
            if ($superAdminCount <= 1) {
                $this->addFlash('error', 'Cannot delete the last super administrator.');
                return $this->redirectToRoute('admin_user_index');
            }
        }

        if ($this->isCsrfTokenValid('delete-' . $administrator->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($administrator);
            $this->entityManager->flush();

            $this->addFlash('success', 'Administrator deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_user_index');
    }

    #[Route('/departments', name: 'admin_user_departments', methods: ['GET'])]
    public function departments(): Response
    {
        $departments = $this->administratorRepository->findAllDepartments();
        
        return $this->json($departments);
    }
}