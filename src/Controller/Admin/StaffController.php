<?php

namespace App\Controller\Admin;

use App\Entity\Admin;
use App\Entity\User;
use App\Repository\AdminRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class StaffController extends AbstractController
{
    #[Route('/staff', name: 'admin_staff', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        AdminRepository $adminRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
    ): Response {
        /** @var Admin|null $currentAdmin */
        $currentAdmin = $this->getUser();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('admin_staff', $request->request->getString('_token'))) {
                $this->addFlash('error', 'Invalid security token. Please try again.');
                return $this->redirectToRoute('admin_staff');
            }

            if ($request->request->has('save_staff')) {
                $editId = $request->request->getInt('edit_id');
                $name = trim($request->request->getString('name'));
                $email = strtolower(trim($request->request->getString('email')));
                $role = trim($request->request->getString('role', Admin::ROLE_STAFF));
                $status = trim($request->request->getString('status', Admin::STATUS_ACTIVE));
                $password = $request->request->getString('password');

                if ($name === '' || $email === '') {
                    $this->addFlash('error', 'Name and email/username are required.');
                    return $this->redirectToRoute('admin_staff');
                }

                if (!in_array($role, [Admin::ROLE_ADMIN, Admin::ROLE_STAFF], true)) {
                    $role = Admin::ROLE_STAFF;
                }
                if (!in_array($status, [Admin::STATUS_ACTIVE, Admin::STATUS_DISABLED, Admin::STATUS_ARCHIVED], true)) {
                    $status = Admin::STATUS_ACTIVE;
                }

                $existing = $adminRepository->createQueryBuilder('a')
                    ->where('LOWER(a.email) = LOWER(:email) AND a.id != :id')
                    ->setParameter('email', $email)
                    ->setParameter('id', $editId > 0 ? $editId : 0)
                    ->getQuery()
                    ->getOneOrNullResult();

                if ($existing) {
                    $this->addFlash('error', 'That email/username is already used by another account.');
                    return $this->redirectToRoute('admin_staff');
                }

                if ($editId > 0) {
                    $staff = $adminRepository->find($editId);
                    if (!$staff) {
                        $this->addFlash('error', 'Staff account not found.');
                        return $this->redirectToRoute('admin_staff');
                    }

                    if ($currentAdmin && $staff->getId() === $currentAdmin->getId()) {
                        if ($status !== Admin::STATUS_ACTIVE) {
                            $this->addFlash('error', 'You cannot disable or archive your own active account.');
                            return $this->redirectToRoute('admin_staff');
                        }
                        if ($role !== Admin::ROLE_ADMIN) {
                            $this->addFlash('error', 'You cannot demote your own admin role.');
                            return $this->redirectToRoute('admin_staff');
                        }
                    }

                    $staff->setName($name)
                        ->setEmail($email)
                        ->setRole($role)
                        ->setStatus($status);

                    if ($password !== '') {
                        if (strlen($password) < 6) {
                            $this->addFlash('error', 'Password must be at least 6 characters.');
                            return $this->redirectToRoute('admin_staff');
                        }
                        $staff->setPassword($passwordHasher->hashPassword($staff, $password));
                    }

                    $entityManager->flush();
                    $this->addFlash('success', sprintf('Account "%s" has been updated.', $name));
                } else {
                    if (strlen($password) < 6) {
                        $this->addFlash('error', 'Password is required and must be at least 6 characters for new accounts.');
                        return $this->redirectToRoute('admin_staff');
                    }

                    $staff = new Admin();
                    $staff->setName($name)
                        ->setEmail($email)
                        ->setRole($role)
                        ->setStatus($status)
                        ->setPassword($passwordHasher->hashPassword($staff, $password));

                    $entityManager->persist($staff);
                    $entityManager->flush();
                    $this->addFlash('success', sprintf('New %s account "%s" has been created.', $role === Admin::ROLE_ADMIN ? 'Admin' : 'Staff', $name));
                }

                return $this->redirectToRoute('admin_staff');
            }

            if ($request->request->has('toggle_status')) {
                $staffId = $request->request->getInt('staff_id');
                $newStatus = trim($request->request->getString('new_status', Admin::STATUS_DISABLED));
                $staff = $adminRepository->find($staffId);

                if (!in_array($newStatus, [Admin::STATUS_ACTIVE, Admin::STATUS_DISABLED, Admin::STATUS_ARCHIVED], true)) {
                    $this->addFlash('error', 'Invalid account status.');
                    return $this->redirectToRoute('admin_staff');
                }

                if ($staff) {
                    if ($currentAdmin && $staff->getId() === $currentAdmin->getId()) {
                        $this->addFlash('error', 'You cannot disable or archive your own account.');
                        return $this->redirectToRoute('admin_staff');
                    }

                    $staff->setStatus($newStatus);
                    $entityManager->flush();
                    $this->addFlash('success', sprintf('Account "%s" status changed to %s.', $staff->getName(), $staff->getStatus()));
                }

                return $this->redirectToRoute('admin_staff');
            }

            if ($request->request->has('delete_staff')) {
                $staffId = $request->request->getInt('staff_id');
                $staff = $adminRepository->find($staffId);

                if ($staff) {
                    if ($currentAdmin && $staff->getId() === $currentAdmin->getId()) {
                        $this->addFlash('error', 'You cannot delete your own currently logged-in account.');
                        return $this->redirectToRoute('admin_staff');
                    }

                    $deletedName = $staff->getName();
                    $entityManager->remove($staff);
                    $entityManager->flush();
                    $this->addFlash('success', sprintf('Account "%s" has been deleted.', $deletedName));
                }

                return $this->redirectToRoute('admin_staff');
            }

            if ($request->request->has('save_guest_user')) {
                $guestId = $request->request->getInt('guest_id');
                $firstName = trim($request->request->getString('first_name'));
                $lastName = trim($request->request->getString('last_name'));
                $email = strtolower(trim($request->request->getString('email')));
                $password = $request->request->getString('password');

                $guest = $userRepository->find($guestId);
                if (!$guest instanceof User) {
                    $this->addFlash('error', 'Guest user account not found.');
                    return $this->redirectToRoute('admin_staff');
                }

                $existing = $userRepository->findOneBy(['email' => $email]);
                if ($existing && $existing->getId() !== $guest->getId()) {
                    $this->addFlash('error', 'That email address is already used by another guest account.');
                    return $this->redirectToRoute('admin_staff');
                }

                $guest->setFirstName($firstName)
                    ->setLastName($lastName)
                    ->setEmail($email)
                    ->setUpdatedAt(new \DateTimeImmutable());

                $violations = $validator->validate($guest);
                if (count($violations) > 0) {
                    $entityManager->refresh($guest);
                    $this->addFlash('error', $violations[0]->getMessage());
                    return $this->redirectToRoute('admin_staff');
                }

                if ($password !== '') {
                    if (strlen($password) < 8) {
                        $entityManager->refresh($guest);
                        $this->addFlash('error', 'New guest password must be at least 8 characters.');
                        return $this->redirectToRoute('admin_staff');
                    }
                    $guest->setPassword($passwordHasher->hashPassword($guest, $password));
                }

                $entityManager->flush();
                $this->addFlash('success', sprintf('Guest user account "%s" has been updated.', $guest->getFullName()));
                return $this->redirectToRoute('admin_staff');
            }

            if ($request->request->has('delete_guest_user')) {
                $guestId = $request->request->getInt('guest_id');
                $guest = $userRepository->find($guestId);

                if ($guest instanceof User) {
                    $deletedName = $guest->getFullName();
                    $entityManager->remove($guest);
                    $entityManager->flush();
                    $this->addFlash('success', sprintf('Guest user account "%s" has been deleted.', $deletedName));
                }

                return $this->redirectToRoute('admin_staff');
            }
        }

        $filterRole = $request->query->getString('role', 'all');
        $filterStatus = $request->query->getString('status', 'all');
        $searchQuery = trim($request->query->getString('search'));

        $staffList = $adminRepository->findAllFiltered(
            $filterRole !== 'all' ? $filterRole : null,
            $filterStatus !== 'all' ? $filterStatus : null,
            $searchQuery !== '' ? $searchQuery : null
        );

        $guestUsers = $userRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/staff.html.twig', [
            'staffList' => $staffList,
            'guestUsers' => $guestUsers,
            'filterRole' => $filterRole,
            'filterStatus' => $filterStatus,
            'searchQuery' => $searchQuery,
            'current_page' => 'staff',
        ]);
    }
}


