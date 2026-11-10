<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        $redirect = $request->query->getString('redirect', '');

        if ($this->getUser()) {
            return $this->redirectToRoute($redirect === 'booking' ? 'booking_index' : 'account_index');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error'         => $authenticationUtils->getLastAuthenticationError(),
            'redirect'      => $redirect,
        ]);
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        UserRepository $userRepo,
        ValidatorInterface $validator,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('account_index');
        }

        $errors     = [];
        $firstName  = '';
        $lastName   = '';
        $email      = '';
        $redirect   = $request->query->getString('redirect', '');

        if ($request->isMethod('POST')) {
            $firstName       = trim($request->request->getString('first_name'));
            $lastName        = trim($request->request->getString('last_name'));
            $email           = strtolower(trim($request->request->getString('email')));
            $password        = $request->request->getString('password');
            $confirmPassword = $request->request->getString('confirm_password');

            $user = new User();
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setEmail($email);

            $violations = $validator->validate($user);
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }

            if (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters.';
            }
            if ($password !== $confirmPassword) {
                $errors[] = 'Passwords do not match.';
            }

            if (empty($errors)) {
                $existing = $userRepo->findOneBy(['email' => $email]);
                if ($existing) {
                    $errors[] = 'An account with this email already exists.';
                }
            }

            if (empty($errors)) {
                $user->setPassword($hasher->hashPassword($user, $password));
                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Account created successfully! Please log in.');
                $redirectParams = $redirect !== '' ? ['redirect' => $redirect] : [];
                return $this->redirectToRoute('app_login', $redirectParams);
            }
        }

        return $this->render('security/register.html.twig', [
            'errors'     => $errors,
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'email'      => $email,
            'redirect'   => $redirect,
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET', 'POST'])]
    public function logout(): never
    {
        throw new \LogicException('Intercepted by the firewall.');
    }
}
