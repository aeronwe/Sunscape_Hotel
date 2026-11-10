<?php

namespace App\Controller\Admin;

use App\Entity\Admin;
use App\Repository\AdminRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_STAFF')]
final class SettingsController extends AbstractController
{
    #[Route('/settings', name: 'admin_settings', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        AdminRepository $adminRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        /** @var Admin|null $currentAdmin */
        $currentAdmin = $this->getUser();

        if (!$currentAdmin instanceof Admin) {
            $currentAdmin = $adminRepository->findOneBy([], ['id' => 'ASC']);
        }

        if ($request->isMethod('POST')) {
            $action = $request->request->getString('action');
            if (!$this->isCsrfTokenValid('admin_settings', $request->request->getString('_token'))) {
                $this->addFlash('error', 'Invalid security token. Please try again.');
                return $this->redirectToRoute('admin_settings');
            }

            $currentPassword = $request->request->getString('superadmin_password');

            if (!$currentAdmin instanceof Admin
                || $currentPassword === ''
                || !$passwordHasher->isPasswordValid($currentAdmin, $currentPassword)
            ) {
                $this->addFlash('error', 'Authorization failed: Incorrect current password.');
                return $this->redirectToRoute('admin_settings');
            }

            if ($action === 'update_profile') {
                $name = trim($request->request->getString('name'));
                $email = trim($request->request->getString('email'));

                if ($name === '') {
                    $this->addFlash('error', 'Please enter a display name.');
                    return $this->redirectToRoute('admin_settings');
                }
                if ($email === '') {
                    $this->addFlash('error', 'Please enter a username / email.');
                    return $this->redirectToRoute('admin_settings');
                }

                $existing = $adminRepository->createQueryBuilder('a')
                    ->where('a.email = :email AND a.id != :id')
                    ->setParameter('email', $email)
                    ->setParameter('id', $currentAdmin ? $currentAdmin->getId() : 0)
                    ->getQuery()
                    ->getOneOrNullResult();

                if ($existing) {
                    $this->addFlash('error', 'This username / email is already in use by another account.');
                    return $this->redirectToRoute('admin_settings');
                }

                if ($currentAdmin) {
                    $currentAdmin->setName($name);
                    $currentAdmin->setEmail($email);
                    $entityManager->flush();
                    $this->addFlash('success', 'Profile and username/email updated successfully!');
                }
                return $this->redirectToRoute('admin_settings');
            }

            if ($action === 'update_password') {
                $newPassword = $request->request->getString('new_password');
                $confirmPassword = $request->request->getString('confirm_password');

                if ($newPassword === '') {
                    $this->addFlash('error', 'Please enter a new password.');
                    return $this->redirectToRoute('admin_settings');
                }
                if (strlen($newPassword) < 6) {
                    $this->addFlash('error', 'New password must be at least 6 characters long.');
                    return $this->redirectToRoute('admin_settings');
                }
                if ($newPassword !== $confirmPassword) {
                    $this->addFlash('error', 'New passwords do not match. Please re-enter.');
                    return $this->redirectToRoute('admin_settings');
                }

                if ($currentAdmin) {
                    $hashed = $passwordHasher->hashPassword($currentAdmin, $newPassword);
                    $currentAdmin->setPassword($hashed);
                    $entityManager->flush();
                    $this->addFlash('success', 'Password changed successfully! Use your new password on your next login.');
                }
                return $this->redirectToRoute('admin_settings');
            }
        }

        return $this->render('admin/settings.html.twig', [
            'currentAdmin' => $currentAdmin,
            'current_page' => 'settings',
        ]);
    }
}
