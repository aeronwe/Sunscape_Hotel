<?php

namespace App\Controller\Admin;

use App\Entity\Admin;
use App\Repository\AdminRepository;
use App\Repository\ReservationRepository;
use App\Repository\RoomRepository;
use App\Repository\ServiceRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class DashboardController extends AbstractController
{
    #[Route('', name: 'admin_dashboard', methods: ['GET'])]
    public function index(
        RoomRepository $rooms,
        ReservationRepository $reservations,
        UserRepository $users,
        ServiceRepository $services,
        AdminRepository $admins
    ): Response {
        $totalRooms = $rooms->count([]);
        $totalServices = $services->count([]);
        $totalReservations = $reservations->count([]);
        $totalGuests = $users->count([]);
        $totalStaffAccounts = $admins->count([]);
        $staffOnlyCount = $admins->count(['role' => Admin::ROLE_STAFF]);
        $adminCount = $admins->count(['role' => Admin::ROLE_ADMIN]);

        $stats = [
            'total_users' => $totalGuests,
            'total_staff' => $totalStaffAccounts,
            'staff_only_count' => $staffOnlyCount,
            'admin_count' => $adminCount,
            'total_records' => $totalRooms + $totalServices + $totalReservations,
            'total_rooms' => $totalRooms,
            'total_reservations' => $totalReservations,
            'pending_reservations' => $reservations->countPending(),
            'today_checkins' => $reservations->countTodayCheckins(new \DateTimeImmutable('today')),
            'total_revenue' => $reservations->sumConfirmedRevenue(),
            'total_guests' => $totalGuests,
            'total_services' => $totalServices,
        ];

        $recentReservations = $reservations->findRecent(10);

        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => $stats,
            'recentReservations' => $recentReservations,
            'current_page' => 'dashboard',
        ]);
    }
}
