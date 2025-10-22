<?php

namespace App\Controller\Admin;

use App\Entity\Administrator;
use App\Form\AdminProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/profile')]
class AdminProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'admin_profile_show', methods: ['GET'])]
    public function show(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        /** @var Administrator $admin */
        $admin = $this->getUser();
        
        return $this->render('admin/profile/show.html.twig', [
            'administrator' => $admin,
        ]);
    }

    #[Route('/edit', name: 'admin_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        /** @var Administrator $admin */
        $admin = $this->getUser();
        
        $form = $this->createForm(AdminProfileType::class, $admin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle password change if provided
            $plainPassword = $form->get('plainPassword')->getData();
            if (!empty($plainPassword)) {
                $encodedPassword = $passwordHasher->hashPassword($admin, $plainPassword);
                $admin->setPassword($encodedPassword);
                $this->addFlash('success', 'Profile updated successfully! Your password has been changed.');
            } else {
                $this->addFlash('success', 'Profile updated successfully!');
            }

            $this->entityManager->flush();

            return $this->redirectToRoute('admin_profile_show');
        }

        return $this->render('admin/profile/edit.html.twig', [
            'administrator' => $admin,
            'form' => $form->createView(),
        ]);
    }
}