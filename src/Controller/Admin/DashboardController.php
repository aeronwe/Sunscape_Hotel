<?php

namespace App\Controller\Admin;

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
        ServiceRepository $services
    ): Response {
        $stats = [
            'total_rooms' => $rooms->count([]),
            'total_reservations' => $reservations->count([]),
            'pending_reservations' => $reservations->countPending(),
            'today_checkins' => $reservations->countTodayCheckins(new \DateTimeImmutable('today')),
            'total_revenue' => $reservations->sumConfirmedRevenue(),
            'total_guests' => $users->count([]),
            'total_services' => $services->count([]),
        ];

        $recentReservations = $reservations->findRecent(10);

        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => $stats,
            'recentReservations' => $recentReservations,
            'current_page' => 'dashboard',
        ]);
    }
}
