<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\ReservationService;
use App\Entity\User;
use App\Repository\RoomRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BookingController extends AbstractController
{
    #[Route('/booking', name: 'booking_index', methods: ['GET', 'POST'])]
    public function index(Request $request, RoomRepository $roomRepo, ServiceRepository $serviceRepo): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('error', 'Please log in or create an account before making a booking.');
            return $this->redirectToRoute('app_login', ['redirect' => 'booking']);
        }

        $session = $request->getSession();
        $bookingSearch = $session->get('booking_search', []);
        $session->remove('booking_search');

        $prefillCheckin = $bookingSearch['checkin'] ?? '';
        $prefillCheckout = $bookingSearch['checkout'] ?? '';
        $prefillAdults = (int) ($bookingSearch['guests'] ?? 2);
        if ($prefillAdults < 1) {
            $prefillAdults = 2;
        }
        $prefillRoomType = $bookingSearch['room_type'] ?? '';

        // Load rooms from DB; fallback to static data
        $dbRooms = $roomRepo->findBy([], ['id' => 'ASC']);
        $rooms = array_map(fn ($r) => [
            'id'    => $r->getId(),
            'name'  => $r->getName(),
            'badge' => $r->getBadge(),
            'price' => (float) $r->getPrice(),
            'image' => $r->getImageUrl(),
        ], $dbRooms);

        if (empty($rooms)) {
            $rooms = $this->fallbackRooms();
        }

        $roomTypeIndex = array_search($prefillRoomType, ['standard', 'deluxe', 'suite'], true);
        if ($roomTypeIndex === false && $prefillRoomType !== '') {
            foreach ($rooms as $idx => $r) {
                if (stripos($r['name'], $prefillRoomType) !== false) {
                    $roomTypeIndex = $idx;
                    break;
                }
            }
        }

        // Load experiences from DB; fallback to static data
        $dbServices = $serviceRepo->findBy([], ['id' => 'ASC']);
        $experiences = [];
        foreach ($dbServices as $s) {
            if ((float) $s->getPrice() > 0 || in_array((int) $s->getId(), [3, 4, 6], true)) {
                $experiences[] = $this->serviceToExperience($s);
            }
        }

        if (empty($experiences)) {
            $experiences = $this->fallbackExperiences();
        }

        $prefillExperience = trim((string) ($request->query->get('experience') ?? $request->query->get('exp') ?? ''));

        return $this->render('booking/index.html.twig', [
            'resortName'        => "Ejercito's Sunscape Resort",
            'rooms'             => $rooms,
            'experiences'       => $experiences,
            'prefillCheckin'    => $prefillCheckin,
            'prefillCheckout'   => $prefillCheckout,
            'prefillAdults'     => $prefillAdults,
            'roomTypeIndex'     => $roomTypeIndex,
            'prefillExperience' => $prefillExperience,
        ]);
    }

    #[Route('/api/booking', name: 'api_booking', methods: ['POST'])]
    #[Route('/api/process_booking.php', name: 'api_process_booking', methods: ['POST'])]
    public function process(
        Request $request,
        EntityManagerInterface $em,
        RoomRepository $roomRepo,
        ServiceRepository $serviceRepo
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['success' => false, 'message' => 'Please log in before making a booking.'], 401);
        }

        $input = json_decode($request->getContent(), true);
        if (!is_array($input)) {
            return $this->json(['success' => false, 'message' => 'Invalid request data.'], 400);
        }

        $fullName              = trim((string) ($input['full_name'] ?? ''));
        $email                 = trim((string) ($input['email'] ?? ''));
        $phone                 = trim((string) ($input['phone'] ?? ''));
        $checkinStr            = trim((string) ($input['checkin_date'] ?? ''));
        $checkoutStr           = trim((string) ($input['checkout_date'] ?? ''));
        $adults                = (int) ($input['adults'] ?? 1);
        $children              = (int) ($input['children'] ?? 0);
        $roomType              = trim((string) ($input['room_type'] ?? ''));
        $paymentMethod         = trim((string) ($input['payment_method'] ?? 'credit_card'));
        $specialReqs           = trim((string) ($input['special_requests'] ?? ''));
        $additionalGuestsInput = $input['additional_guests'] ?? [];
        $experiencesInput      = $input['experiences'] ?? [];

        // Format selected experiences into special requests note if present
        if (!empty($experiencesInput) && is_array($experiencesInput)) {
            $expLines = [];
            foreach ($experiencesInput as $exp) {
                $eName  = trim((string) ($exp['name'] ?? ''));
                $eCount = (int) ($exp['guests'] ?? 1);
                if ($eName !== '') {
                    $guestStr = $eCount === 1 ? '1 guest' : "{$eCount} guests";
                    $expLines[] = "- {$eName} ({$guestStr})";
                }
            }
            if (!empty($expLines)) {
                $expNote = "Selected Experiences:\n" . implode("\n", $expLines);
                $specialReqs = $specialReqs !== '' ? $specialReqs . "\n\n" . $expNote : $expNote;
            }
        }

        // Validate
        $errors = [];
        if ($fullName === '') {
            $errors[] = 'Full name is required.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }
        if ($phone === '') {
            $errors[] = 'Phone number is required.';
        }
        if ($roomType === '') {
            $errors[] = 'Please select a room type.';
        }
        if ($checkinStr === '' || $checkoutStr === '') {
            $errors[] = 'Check-in and check-out dates are required.';
        }

        $checkin  = \DateTimeImmutable::createFromFormat('Y-m-d', $checkinStr);
        $checkout = \DateTimeImmutable::createFromFormat('Y-m-d', $checkoutStr);

        if (!$checkin || !$checkout || $checkin->format('Y-m-d') !== $checkinStr
            || $checkout->format('Y-m-d') !== $checkoutStr || $checkin >= $checkout) {
            $errors[] = 'Check-out date must be after check-in date.';
        }

        if ($adults < 1) {
            $errors[] = 'At least 1 adult is required.';
        }
        if ($paymentMethod === '') {
            $errors[] = 'Please select a payment method.';
        }
        $validGuests = [];
        if (is_array($additionalGuestsInput)) {
            foreach ($additionalGuestsInput as $g) {
                $name = trim((string) $g);
                if ($name !== '') {
                    $validGuests[] = $name;
                }
            }
        }

        if (!empty($errors)) {
            return $this->json(['success' => false, 'message' => implode(' ', $errors), 'errors' => $errors], 422);
        }

        $roomId = !empty($input['room_id']) ? (int) $input['room_id'] : null;
        $roomEntity = $roomId ? $roomRepo->find($roomId) : null;
        if (!$roomEntity && $roomType !== '') {
            $roomEntity = $roomRepo->findOneBy(['name' => $roomType]);
        }

        if (!$roomEntity) {
            return $this->json(['success' => false, 'message' => 'The selected room is no longer available.'], 422);
        }

        $nights = $checkout->diff($checkin)->days;
        $totalAmount = $roomEntity->getPrice() * $nights;
        $selectedServices = [];

        if (!empty($experiencesInput) && is_array($experiencesInput)) {
            foreach ($experiencesInput as $exp) {
                $serviceId = !empty($exp['id']) && is_numeric($exp['id']) ? (int) $exp['id'] : null;
                $eName = trim((string) ($exp['name'] ?? ''));
                $eGuests = max(1, (int) ($exp['guests'] ?? 1));

                $serviceEntity = $serviceId ? $serviceRepo->find($serviceId) : null;
                if (!$serviceEntity && $eName !== '') {
                    $serviceEntity = $serviceRepo->findOneBy(['name' => $eName]);
                }

                if (!$serviceEntity) {
                    return $this->json(['success' => false, 'message' => 'One of the selected experiences is no longer available.'], 422);
                }

                $totalAmount += $serviceEntity->getPrice() * $eGuests;
                $selectedServices[] = [$serviceEntity, $eGuests];
            }
        }

        try {
            $reservation = new Reservation();
            $reservation->setUser($user);
            $reservation->setFullName($fullName);
            $reservation->setEmail($email);
            $reservation->setPhone($phone);
            $reservation->setRoomType($roomType);
            $reservation->setCheckinDate($checkin);
            $reservation->setCheckoutDate($checkout);
            $reservation->setAdults($adults);
            $reservation->setChildren($children);
            $reservation->setAdditionalGuests(!empty($validGuests) ? json_encode($validGuests) : null);
            $reservation->setPaymentMethod($paymentMethod);
            $reservation->setSpecialRequests($specialReqs ?: null);
            $reservation->setTotalAmount($totalAmount);
            $reservation->setStatus('pending');

            $reservation->setRoom($roomEntity);
            $reservation->setRoomType($roomEntity->getName());

            $em->persist($reservation);

            foreach ($selectedServices as [$serviceEntity, $eGuests]) {
                $resService = new ReservationService();
                $resService->setReservation($reservation);
                $resService->setService($serviceEntity);
                $resService->setGuests($eGuests);
                $resService->setPrice($serviceEntity->getPrice());
                $em->persist($resService);
            }

            $em->flush();

            return $this->json([
                'success'        => true,
                'reservation_id' => $reservation->getId(),
                'message'        => 'Your reservation has been confirmed!',
            ]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    private function serviceToExperience(object $s): array
    {
        $name = $s->getName();
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

        if (stripos($name, 'malaya') !== false) {
            $route = 'malaya_tours';
        } elseif (stripos($name, 'bar') !== false) {
            $route = 'island_bar';
        } elseif (stripos($name, 'grill') !== false || stripos($name, 'bbq') !== false || stripos($name, 'coast') !== false) {
            $route = 'beach_bbq';
        } elseif (stripos($name, 'juwan') !== false) {
            $route = 'el_juwan';
        } else {
            $route = 'experience_index';
        }

        $badge = $s->getTag() ?: 'Experience';
        if ($s->getHours()) {
            $badge = $s->getHours();
        }

        $imageUrl = trim((string) ($s->getImageUrl() ?? ''));
        if ($imageUrl !== '' && !preg_match('/^(https?:)?\/\//i', $imageUrl)) {
            $clean = preg_replace('#^(?:\.\.?/)+#', '', $imageUrl);
            $clean = preg_replace('#^assets/#', '', $clean);
            if (!str_starts_with($clean, 'images/')) {
                $clean = 'images/' . ltrim($clean, '/');
            }
            $imageUrl = $clean;
        }

        return [
            'id'          => $slug,
            'service_id'  => $s->getId(),
            'name'        => $name,
            'badge'       => $badge,
            'price'       => (float) $s->getPrice() ?: 500,
            'description' => $s->getDescription() ?? '',
            'image'       => $imageUrl,
            'page'        => $route,
            'route'       => $route,
        ];
    }

    private function fallbackRooms(): array
    {
        return [
            ['id' => 1, 'name' => 'Standard Twin Room',       'badge' => 'Standard', 'price' => 3200,  'image' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=900&q=80'],
            ['id' => 2, 'name' => 'Deluxe Garden View',       'badge' => 'Deluxe',   'price' => 4500,  'image' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=900&q=80'],
            ['id' => 3, 'name' => 'Premier Ocean Suite',      'badge' => 'Suite',    'price' => 8200,  'image' => 'https://a0.muscache.com/im/pictures/hosting/Hosting-22680436/original/42f1ab5b-f7fb-4287-ba13-e34f1758563d.jpeg?im_w=1200'],
            ['id' => 4, 'name' => 'Family Beach Villa',        'badge' => 'Villa',    'price' => 12500, 'image' => 'https://images.squarespace-cdn.com/content/v1/5b4f0c8d89c17294e53d4ffc/1532678056046-2I393HL258IGMVG5LB7Z/351bbf9b32ea226d4294d111dad38ed0.jpg?format=2500w'],
            ['id' => 5, 'name' => 'Honeymoon Paradise Suite', 'badge' => 'Premium',  'price' => 15000, 'image' => 'https://media.cntraveller.com/photos/611bf43e69410e829d87eb1a/16:9/w_1920,c_limit/pangulasian_cnt_17sept12_pr.jpg'],
        ];
    }

    private function fallbackExperiences(): array
    {
        return [
            ['id' => 'malaya-tours', 'service_id' => 4, 'name' => 'Malaya Tours Island Hopping Adventure', 'badge' => 'Full Day', 'price' => 3500, 'description' => 'Explore hidden coves, sandbars, and snorkeling spots around Siquijor.', 'image' => 'images/island_hopping.jpg', 'page' => 'malaya_tours', 'route' => 'malaya_tours'],
            ['id' => 'island-bar',   'service_id' => 6, 'name' => 'Island Bar & Clvb',                    'badge' => '2 Hours',  'price' => 800,  'description' => 'Party together while on vacation with DJ, drinks, and good vibes.', 'image' => 'images/island_bar.jpg',      'page' => 'island_bar',   'route' => 'island_bar'],
            ['id' => 'coast-grill',  'service_id' => 3, 'name' => 'Coast Grilled Nights',                 'badge' => '3 Hours',  'price' => 500,  'description' => 'Freshly grilled seafood and meat while enjoying the night.',      'image' => 'images/Beach-BBQ-9.jpg',     'page' => 'beach_bbq',    'route' => 'beach_bbq'],
        ];
    }
}
