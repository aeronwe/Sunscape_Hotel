<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/account')]
#[IsGranted('ROLE_USER')]
final class AccountController extends AbstractController
{
    public function __construct(private readonly TokenStorageInterface $tokenStorage)
    {
    }

    #[Route('', name: 'account_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        ReservationRepository $reservations,
        UserRepository $userRepo,
        UserPasswordHasherInterface $hasher,
        ValidatorInterface $validator,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $errors = [];
        $successMsg = '';

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('account', $request->request->getString('_token'))) {
                $errors[] = 'Invalid security token. Please refresh the page and try again.';
                return $this->render('account/index.html.twig', [
                    'user'         => $user,
                    'reservations' => $reservations->findForUser($user),
                    'errors'       => $errors,
                    'success_msg'  => $successMsg,
                ]);
            }

            if ($request->request->has('delete_account')) {
                $em->remove($user);
                $em->flush();
                $this->tokenStorage->setToken(null);
                $request->getSession()->invalidate();
                $this->addFlash('success', 'Your account has been deleted.');
                return $this->redirectToRoute('app_login');
            }

            if ($request->request->has('change_password')) {
                $currentPassword = $request->request->getString('current_password');
                $newPassword     = $request->request->getString('new_password');
                $confirmPassword = $request->request->getString('confirm_password');

                if ($currentPassword === '' || !$hasher->isPasswordValid($user, $currentPassword)) {
                    $errors[] = 'Current password is incorrect.';
                }
                if (strlen($newPassword) < 8) {
                    $errors[] = 'New password must be at least 8 characters.';
                }
                if ($newPassword !== $confirmPassword) {
                    $errors[] = 'New passwords do not match.';
                }

                if (empty($errors)) {
                    $user->setPassword($hasher->hashPassword($user, $newPassword));
                    $user->setUpdatedAt(new \DateTimeImmutable());
                    $em->flush();
                    $successMsg = 'Password changed successfully!';
                }
            }

            if ($request->request->has('update_profile')) {
                $firstName = trim($request->request->getString('first_name'));
                $lastName  = trim($request->request->getString('last_name'));
                $email     = strtolower(trim($request->request->getString('email')));

                $existing = $userRepo->findOneBy(['email' => $email]);
                if ($existing && $existing->getId() !== $user->getId()) {
                    $errors[] = 'This email is already used by another account.';
                }

                $user->setFirstName($firstName);
                $user->setLastName($lastName);
                $user->setEmail($email);

                $violations = $validator->validate($user);
                foreach ($violations as $violation) {
                    $errors[] = $violation->getMessage();
                }

                // Also support optional password update if submitted with update_profile
                $newPassword = $request->request->getString('new_password');
                if ($newPassword !== '') {
                    $currentPassword = $request->request->getString('current_password');
                    $confirmPassword = $request->request->getString('confirm_password');

                    if ($currentPassword === '' || !$hasher->isPasswordValid($user, $currentPassword)) {
                        $errors[] = 'Current password is incorrect.';
                    }
                    if (strlen($newPassword) < 8) {
                        $errors[] = 'New password must be at least 8 characters.';
                    }
                    if ($newPassword !== $confirmPassword) {
                        $errors[] = 'New passwords do not match.';
                    }
                    if (empty($errors)) {
                        $user->setPassword($hasher->hashPassword($user, $newPassword));
                    }
                }

                if (empty($errors)) {
                    $user->setUpdatedAt(new \DateTimeImmutable());
                    $em->flush();
                    $successMsg = 'Profile updated successfully!';
                } else {
                    $em->refresh($user);
                }
            }
        }

        $userReservations = $reservations->findForUser($user);

        return $this->render('account/index.html.twig', [
            'user'         => $user,
            'reservations' => $userReservations,
            'errors'       => $errors,
            'success_msg'  => $successMsg,
        ]);
    }
}
