<?php

namespace App\Controller;

use App\Repository\RoomRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(Request $request, RoomRepository $roomRepository): Response
    {
        // Handle availability search form submission
        if ($request->isMethod('POST') && $request->request->has('check_availability')) {
            return $this->handleAvailabilitySearch($request);
        }

        // Fetch featured rooms from DB (first 3), with static fallback
        $dbRooms = $roomRepository->findFeatured(3);
        $featuredRooms = array_map(
            fn($room) => [
                'id'          => $room->getId(),
                'slug'        => $room->getSlug(),
                'name'        => $room->getName(),
                'badge'       => $room->getBadge(),
                'badge_class' => $room->getBadgeClass(),
                'specs'       => $room->getSpecs(),
                'price'       => '₱' . number_format($room->getPrice(), 0),
                'image_url'   => $room->getImageUrl(),
            ],
            $dbRooms
        );

        // Static fallback if DB is empty or unavailable
        if (empty($featuredRooms)) {
            $featuredRooms = [
                [
                    'slug'        => 'standard-twin',
                    'name'        => 'Standard Twin Room',
                    'badge'       => 'Standard',
                    'badge_class' => 'standard',
                    'specs'       => '30 sqm  ·  2 Guests  ·  2 Beds',
                    'price'       => '₱3,200',
                    'image_url'   => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=900&q=80',
                ],
                [
                    'slug'        => 'deluxe-garden',
                    'name'        => 'Deluxe Garden View',
                    'badge'       => 'Deluxe',
                    'badge_class' => 'deluxe',
                    'specs'       => '38 sqm  ·  2 Guests  ·  King',
                    'price'       => '₱4,500',
                    'image_url'   => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=900&q=80',
                ],
                [
                    'slug'        => 'premier-ocean',
                    'name'        => 'Premier Ocean Suite',
                    'badge'       => 'Suite',
                    'badge_class' => 'suite',
                    'specs'       => '58 sqm  ·  3 Guests  ·  King + Extra Bed',
                    'price'       => '₱8,200',
                    'image_url'   => 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&w=900&q=80',
                ],
            ];
        }

        return $this->render('home/index.html.twig', [
            'featuredRooms' => $featuredRooms,
        ]);
    }

    private function handleAvailabilitySearch(Request $request): Response
    {
        $checkin   = strip_tags(trim($request->request->getString('checkin')));
        $checkout  = strip_tags(trim($request->request->getString('checkout')));
        $guests    = $request->request->getInt('guests', 2);
        $roomType  = strip_tags(trim($request->request->getString('room_type', 'any')));

        $allowedRoomTypes = ['any', 'standard', 'deluxe', 'suite'];
        $checkinDate      = \DateTimeImmutable::createFromFormat('Y-m-d', $checkin);
        $checkoutDate     = \DateTimeImmutable::createFromFormat('Y-m-d', $checkout);

        $valid = $checkinDate
            && $checkoutDate
            && $checkinDate->format('Y-m-d') === $checkin
            && $checkoutDate->format('Y-m-d') === $checkout
            && $checkin < $checkout
            && $guests >= 1 && $guests <= 4
            && in_array($roomType, $allowedRoomTypes, true);

        if (!$valid) {
            $this->addFlash('error', 'Please choose valid check-in, check-out, guest, and room details.');
            return $this->redirectToRoute('home', ['_fragment' => 'booking']);
        }

        $request->getSession()->set('booking_search', [
            'checkin'   => $checkin,
            'checkout'  => $checkout,
            'guests'    => $guests,
            'room_type' => $roomType,
        ]);

        return $this->redirectToRoute('booking_index');
    }
}
