<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
                    'reservations' => [],
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

            if ($request->request->has('update_profile')) {
                $firstName = trim($request->request->getString('first_name'));
                $lastName  = trim($request->request->getString('last_name'));
                $email     = strtolower(trim($request->request->getString('email')));

                if (strlen($firstName) < 2) {
                    $errors[] = 'First name must be at least 2 characters.';
                }
                if (strlen($lastName) < 2) {
                    $errors[] = 'Last name must be at least 2 characters.';
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Please enter a valid email address.';
                }

                if (empty($errors)) {
                    $existing = $userRepo->findOneBy(['email' => $email]);
                    if ($existing && $existing->getId() !== $user->getId()) {
                        $errors[] = 'This email is already used by another account.';
                    }
                }

                if (empty($errors)) {
                    $user->setFirstName($firstName);
                    $user->setLastName($lastName);
                    $user->setEmail($email);
                    $user->setUpdatedAt(new \DateTimeImmutable());
                    $em->flush();
                    $successMsg = 'Profile updated successfully!';
                }
            }
        }

        $userReservations = [];
        try {
            $userReservations = $reservations->findForUser($user);
        } catch (\Throwable) {}

        return $this->render('account/index.html.twig', [
            'user'         => $user,
            'reservations' => $userReservations,
            'errors'       => $errors,
            'success_msg'  => $successMsg,
        ]);
    }
}
