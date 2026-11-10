<?php

namespace App\Controller;

use App\Repository\RoomRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/rooms')]
final class RoomController extends AbstractController
{
    #[Route('', name: 'room_index', methods: ['GET'])]
    public function index(Request $request, RoomRepository $rooms): Response
    {
        $roomList = array_map(fn ($room) => $this->roomData($room), $rooms->findBy([], ['id' => 'ASC']));

        if (!$roomList) {
            $roomList = $this->fallbackRooms();
        }

        $type   = $request->query->getString('type', 'all');
        $price  = $request->query->getString('price', 'all');
        $guests = $request->query->getString('guests', 'all');
        $sort   = $request->query->getString('sort', 'featured');

        $roomList = array_values(array_filter($roomList, function (array $room) use ($type, $price, $guests): bool {
            if ($type !== 'all' && strtolower($room['badge']) !== $type) {
                return false;
            }
            if ($price === 'under-5000' && $room['price_value'] >= 5000) {
                return false;
            }
            if ($price === '5000-10000' && ($room['price_value'] < 5000 || $room['price_value'] > 10000)) {
                return false;
            }
            if ($price === 'over-10000' && $room['price_value'] <= 10000) {
                return false;
            }
            if ($guests === '1-2' && $room['max_guests'] > 2) {
                return false;
            }
            if ($guests === '3-4' && ($room['max_guests'] < 3 || $room['max_guests'] > 4)) {
                return false;
            }
            if ($guests === '5-plus' && $room['max_guests'] < 5) {
                return false;
            }

            return true;
        }));

        usort($roomList, match ($sort) {
            'price-low'  => fn (array $a, array $b): int => $a['price_value'] <=> $b['price_value'],
            'price-high' => fn (array $a, array $b): int => $b['price_value'] <=> $a['price_value'],
            'name'       => fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']),
            default      => fn (array $a, array $b): int => $a['position'] <=> $b['position'],
        });

        return $this->render('room/index.html.twig', compact('roomList', 'type', 'price', 'guests', 'sort'));
    }

    #[Route('/{slug}', name: 'room_detail', methods: ['GET'])]
    public function detail(string $slug, RoomRepository $rooms): Response
    {
        $allRoomsData = $this->getAllRoomsDetailData();
        $room = $allRoomsData[$slug] ?? null;

        if (!$room) {
            // Check DB
            $dbRoom = $rooms->findBySlug($slug);
            if ($dbRoom) {
                $room = $this->roomData($dbRoom);
                $room['images'] = [$dbRoom->getImageUrl()];
                $room['sqm'] = 35;
                $room['guests'] = 2;
                $room['beds'] = 'Queen Bed';
            }
        }

        if (!$room) {
            return $this->redirectToRoute('room_index');
        }

        $testimonials = [
            ['text' => 'Beautiful resort, amazing views, and such a relaxing atmosphere. We loved every minute of our stay!', 'name' => 'Maria S.', 'stars' => 5],
            ['text' => 'From the food to the service, everything was wonderful. A beautiful place to relax with family.', 'name' => 'Sofia M.', 'stars' => 5],
            ['text' => 'The rooms were comfortable, the staff were welcoming, and the island experiences are unforgettable.', 'name' => 'James R.', 'stars' => 5],
            ['text' => 'A perfect combination of comfort, nature, and Filipino hospitality. Highly recommended!', 'name' => 'Rachel P.', 'stars' => 5],
            ['text' => 'Perfect place for a peaceful getaway in Siquijor. We\'ll definitely be coming back!', 'name' => 'Angelo D.', 'stars' => 5],
            ['text' => 'The sunset by the beach was unforgettable. We had such a relaxing and enjoyable stay.', 'name' => 'Kevin L.', 'stars' => 5],
        ];

        return $this->render('room/detail.html.twig', [
            'room'         => $room,
            'testimonials' => $testimonials,
        ]);
    }

    private function roomData(object $room): array
    {
        preg_match('/(\d+)\s*Guests?/i', $room->getSpecs(), $matches);

        return [
            'id'          => $room->getId(),
            'slug'        => $room->getSlug(),
            'name'        => $room->getName(),
            'badge'       => $room->getBadge(),
            'badge_class' => $room->getBadgeClass(),
            'specs'       => $room->getSpecs(),
            'price'       => (float) $room->getPrice(),
            'price_value' => (float) $room->getPrice(),
            'max_guests'  => (int) ($matches[1] ?? 2),
            'description' => $room->getDescription() ?? '',
            'image'       => $room->getImageUrl(),
            'position'    => $room->getId() ?? 0,
            'amenities'   => ['Air Conditioning', 'Free Wi-Fi', 'Smart TV', 'Mini Bar', 'Rain Shower', 'Coffee & Tea Maker', 'Concierge Service'],
        ];
    }

    private function getAllRoomsDetailData(): array
    {
        return [
            'standard-twin' => [
                'slug'        => 'standard-twin',
                'name'        => 'Standard Twin Room',
                'badge'       => 'Standard',
                'badge_class' => 'standard',
                'sqm'         => 30,
                'guests'      => 2,
                'beds'        => '2 Single Beds',
                'price'       => 3200,
                'description' => 'A comfortable retreat for easy island stays, with thoughtful amenities and a calm garden outlook. Perfect for friends or solo travelers seeking value without compromising comfort.',
                'images'      => [
                    'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=400&q=80',
                    'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=400&q=60',
                    'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=400&q=70',
                ],
                'amenities'   => ['Air Conditioning', 'Free Wi-Fi', '32" Smart TV', 'Mini Bar', 'Garden-View Window', 'Rain Shower', 'Coffee & Tea Maker', 'Concierge Service'],
            ],
            'deluxe-garden' => [
                'slug'        => 'deluxe-garden',
                'name'        => 'Deluxe Garden View',
                'badge'       => 'Deluxe',
                'badge_class' => 'deluxe',
                'sqm'         => 38,
                'guests'      => 2,
                'beds'        => 'King Bed',
                'price'       => 4500,
                'description' => 'Wake up to tropical greenery in a spacious room designed for a slower, more comfortable escape. Enjoy premium amenities and a private garden-view terrace perfect for morning coffee.',
                'images'      => [
                    'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1000&q=80',
                    'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=400&q=80',
                    'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=400&q=60',
                    'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=400&q=70',
                ],
                'amenities'   => ['Air Conditioning', 'Free Wi-Fi', '43" Smart TV', 'Mini Bar', 'Garden-View Terrace', 'Rain Shower', 'Espresso Machine', 'Concierge Service'],
            ],
            'premier-ocean' => [
                'slug'        => 'premier-ocean',
                'name'        => 'Premier Ocean Suite',
                'badge'       => 'Suite',
                'badge_class' => 'suite',
                'sqm'         => 58,
                'guests'      => 3,
                'beds'        => 'King + Sofa Bed',
                'price'       => 8200,
                'description' => 'Luxurious suite with panoramic ocean views, separate living area, and premium amenities. The perfect retreat for those seeking the finest island experience with generous space and refined comfort.',
                'images'      => [
                    'https://a0.muscache.com/im/pictures/hosting/Hosting-22680436/original/42f1ab5b-f7fb-4287-ba13-e34f1758563d.jpeg?im_w=1200',
                    'https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&w=400&q=80',
                    'https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&w=400&q=60',
                    'https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&w=400&q=70',
                ],
                'amenities'   => ['Air Conditioning', 'Free Wi-Fi', '65" Smart TV', 'Full Bar', 'Ocean-View Terrace', 'Jacuzzi Tub', 'Espresso Machine', 'Concierge Service'],
            ],
            'family-villa' => [
                'slug'        => 'family-villa',
                'name'        => 'Family Beach Villa',
                'badge'       => 'Villa',
                'badge_class' => 'suite',
                'sqm'         => 85,
                'guests'      => 5,
                'beds'        => '2 Queen Beds',
                'price'       => 12500,
                'description' => 'A private, easygoing base for families who want more room to gather, rest, and explore. Features separate living and dining areas with direct beach access and a private garden.',
                'images'      => [
                    'https://images.squarespace-cdn.com/content/v1/5b4f0c8d89c17294e53d4ffc/1532678056046-2I393HL258IGMVG5LB7Z/351bbf9b32ea226d4294d111dad38ed0.jpg?format=2500w',
                    'https://images.unsplash.com/photo-1601918774946-25832a4be0d6?auto=format&fit=crop&w=400&q=80',
                    'https://images.unsplash.com/photo-1601918774946-25832a4be0d6?auto=format&fit=crop&w=400&q=60',
                    'https://images.unsplash.com/photo-1601918774946-25832a4be0d6?auto=format&fit=crop&w=400&q=70',
                ],
                'amenities'   => ['Air Conditioning', 'Free Wi-Fi', '55" Smart TV', 'Full Kitchen', 'Beach-Access Terrace', 'Rain Shower + Tub', 'Espresso Machine', 'Private Garden'],
            ],
            'honeymoon-paradise' => [
                'slug'        => 'honeymoon-paradise',
                'name'        => 'Honeymoon Paradise Suite',
                'badge'       => 'Premium',
                'badge_class' => 'suite',
                'sqm'         => 72,
                'guests'      => 2,
                'beds'        => 'King Bed',
                'price'       => 15000,
                'description' => 'A romantic hideaway with the space and privacy to make a special island holiday feel timeless. Features a private plunge pool, sunset terrace, and complimentary champagne on arrival.',
                'images'      => [
                    'https://media.cntraveller.com/photos/611bf43e69410e829d87eb1a/16:9/w_1920,c_limit/pangulasian_cnt_17sept12_pr.jpg',
                    'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?auto=format&fit=crop&w=400&q=80',
                    'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?auto=format&fit=crop&w=400&q=60',
                    'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?auto=format&fit=crop&w=400&q=70',
                ],
                'amenities'   => ['Air Conditioning', 'Free Wi-Fi', '65" Smart TV', 'Full Bar', 'Sunset Terrace', 'Private Plunge Pool', 'Espresso Machine', 'Butler Service'],
            ],
        ];
    }

    private function fallbackRooms(): array
    {
        $rooms = [
            ['slug' => 'standard-twin', 'name' => 'Standard Twin Room', 'badge' => 'Standard', 'badge_class' => 'standard', 'specs' => '30 sqm · 2 Guests · 2 Beds', 'price_value' => 3200, 'description' => 'A comfortable retreat for easy island stays, with thoughtful amenities and a calm garden outlook.', 'image' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1000&q=80', 'max_guests' => 2],
            ['slug' => 'deluxe-garden', 'name' => 'Deluxe Garden View', 'badge' => 'Deluxe', 'badge_class' => 'deluxe', 'specs' => '38 sqm · 2 Guests · King Bed', 'price_value' => 4500, 'description' => 'Wake up to tropical greenery in a spacious room designed for a slower, more comfortable escape.', 'image' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1000&q=80', 'max_guests' => 2],
            ['slug' => 'premier-ocean', 'name' => 'Premier Ocean Suite', 'badge' => 'Suite', 'badge_class' => 'suite', 'specs' => '58 sqm · 3 Guests · King + Extra Bed', 'price_value' => 8200, 'description' => 'Luxurious suite with panoramic ocean views, separate living area, and premium amenities.', 'image' => 'https://a0.muscache.com/im/pictures/hosting/Hosting-22680436/original/42f1ab5b-f7fb-4287-ba13-e34f1758563d.jpeg?im_w=1200', 'max_guests' => 3],
            ['slug' => 'family-villa', 'name' => 'Family Beach Villa', 'badge' => 'Villa', 'badge_class' => 'suite', 'specs' => '85 sqm · 5 Guests · 2 Beds', 'price_value' => 12500, 'description' => 'A private, easygoing base for families who want more room to gather, rest, and explore.', 'image' => 'https://images.squarespace-cdn.com/content/v1/5b4f0c8d89c17294e53d4ffc/1532678056046-2I393HL258IGMVG5LB7Z/351bbf9b32ea226d4294d111dad38ed0.jpg?format=2500w', 'max_guests' => 5],
            ['slug' => 'honeymoon-paradise', 'name' => 'Honeymoon Paradise Suite', 'badge' => 'Premium', 'badge_class' => 'suite', 'specs' => '72 sqm · 2 Guests · King Bed', 'price_value' => 15000, 'description' => 'A romantic hideaway with the space and privacy to make a special island holiday feel timeless.', 'image' => 'https://media.cntraveller.com/photos/611bf43e69410e829d87eb1a/16:9/w_1920,c_limit/pangulasian_cnt_17sept12_pr.jpg', 'max_guests' => 2],
        ];

        foreach ($rooms as $index => &$room) {
            $room['id'] = $index + 1;
            $room['price'] = $room['price_value'];
            $room['position'] = $index;
            $room['amenities'] = ['Air Conditioning', 'Free Wi-Fi', 'Smart TV', 'Mini Bar', 'Rain Shower', 'Coffee & Tea Maker', 'Concierge Service'];
        }

        return $rooms;
    }
}
