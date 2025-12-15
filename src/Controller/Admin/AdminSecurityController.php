<?php

namespace App\Controller\Admin;

use App\Entity\Administrator;
use App\Repository\AdministratorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/admin')]
class AdminSecurityController extends AbstractController
{
    #[Route('/login', name: 'admin_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('admin/security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'admin_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/forgot-password', name: 'admin_forgot_password')]
    public function forgotPassword(
        Request $request,
        AdministratorRepository $adminRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            
            if (empty(trim($email))) {
                $this->addFlash('error', 'Please provide your email address.');
                return $this->redirectToRoute('admin_forgot_password');
            }

            $admin = $adminRepository->findOneBy(['email' => $email]);
            
            // Always show success message for security (don't reveal if email exists)
            if ($admin) {
                // Generate reset token
                $admin->generatePasswordResetToken();
                $entityManager->persist($admin);
                $entityManager->flush();

                // Send reset email
                $resetUrl = $this->generateUrl('admin_reset_password', [
                    'token' => $admin->getPasswordResetToken()
                ], true);

                $emailMessage = (new Email())
                    ->from('noreply@example.com')
                    ->to($admin->getEmail())
                    ->subject('Password Reset Request')
                    ->html($this->renderView('admin/security/reset_email.html.twig', [
                        'admin' => $admin,
                        'reset_url' => $resetUrl,
                    ]));

                $mailer->send($emailMessage);
            }

            $this->addFlash('success', 'If an account exists with that email, a password reset link has been sent.');
            return $this->redirectToRoute('admin_login');
        }

        return $this->render('admin/security/forgot_password.html.twig');
    }

    #[Route('/reset-password/{token}', name: 'admin_reset_password')]
    public function resetPassword(
        string $token,
        Request $request,
        AdministratorRepository $adminRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $admin = $adminRepository->findOneBy(['passwordResetToken' => $token]);

        if (!$admin || !$admin->isPasswordResetTokenValid()) {
            $this->addFlash('error', 'This password reset link is invalid or has expired.');
            return $this->redirectToRoute('admin_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');

            if (empty($password) || strlen($password) < 8) {
                $this->addFlash('error', 'Password must be at least 8 characters long.');
            } elseif ($password !== $confirmPassword) {
                $this->addFlash('error', 'Passwords do not match.');
            } else {
                // Update password
                $hashedPassword = $passwordHasher->hashPassword($admin, $password);
                $admin->setPassword($hashedPassword);
                $admin->clearPasswordResetToken();
                
                $entityManager->persist($admin);
                $entityManager->flush();

                $this->addFlash('success', 'Your password has been reset successfully. You can now log in.');
                return $this->redirectToRoute('admin_login');
            }
        }

        return $this->render('admin/security/reset_password.html.twig', [
            'token' => $token,
        ]);
    }
}
