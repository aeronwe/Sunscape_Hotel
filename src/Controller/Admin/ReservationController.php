<?php

namespace App\Controller\Admin;

use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class ReservationController extends AbstractController
{
    #[Route('/reservations', name: 'admin_reservations', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('admin_reservations', $request->request->getString('_token'))) {
                $this->addFlash('error', 'Invalid security token. Please try again.');
                return $this->redirectToRoute('admin_reservations');
            }

            if ($request->request->has('update_status')) {
                $resId = $request->request->getInt('reservation_id');
                $newStatus = trim($request->request->getString('new_status'));

                $reservation = $reservationRepository->find($resId);
                if ($reservation && in_array($newStatus, ['pending', 'confirmed', 'cancelled'], true)) {
                    $reservation->setStatus($newStatus);
                    $entityManager->flush();
                    $this->addFlash('success', "Reservation #$resId updated to $newStatus.");
                }
                return $this->redirectToRoute('admin_reservations');
            }

            if ($request->request->has('delete_reservation')) {
                $resId = $request->request->getInt('reservation_id');
                $reservation = $reservationRepository->find($resId);
                if ($reservation) {
                    $entityManager->remove($reservation);
                    $entityManager->flush();
                    $this->addFlash('success', "Reservation #$resId has been deleted.");
                }
                return $this->redirectToRoute('admin_reservations');
            }
        }

        $filterStatus = $request->query->getString('status', 'all');
        $searchQuery = trim($request->query->getString('search'));

        $reservations = $reservationRepository->findAllFiltered(
            $filterStatus !== 'all' ? $filterStatus : null,
            $searchQuery !== '' ? $searchQuery : null
        );

        return $this->render('admin/reservations.html.twig', [
            'reservations' => $reservations,
            'filterStatus' => $filterStatus,
            'searchQuery' => $searchQuery,
            'current_page' => 'reservations',
        ]);
    }
}
