<?php

namespace App\Tests\Controller;

use App\Entity\Admin;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Entity\Service;
use App\Repository\AdminRepository;
use App\Repository\ReservationRepository;
use App\Repository\RoomRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminControllerTest extends WebTestCase
{
    public function testUnauthenticatedRedirectsToLogin(): void
    {
        $client = static::createClient();

        $protectedUrls = [
            '/admin',
            '/admin/reservations',
            '/admin/rooms',
            '/admin/services',
            '/admin/settings',
        ];

        foreach ($protectedUrls as $url) {
            $client->request('GET', $url);
            $this->assertResponseRedirects('/admin/login', 302, sprintf('Expected %s to redirect to /admin/login', $url));
        }
    }

    public function testAdminPagesAccessibleWhenLoggedIn(): void
    {
        $client = static::createClient();
        $adminRepo = static::getContainer()->get(AdminRepository::class);
        $admin = $adminRepo->findOneBy([]) ?? $this->createTestAdmin();

        $client->loginUser($admin, 'admin');

        // 1. Dashboard
        $client->request('GET', '/admin');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.admin-sidebar');
        $this->assertAnySelectorTextContains('h1, h2, h3, .admin-header', 'Dashboard');

        // 2. Reservations
        $client->request('GET', '/admin/reservations');
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('h1, h2, h3, .admin-header', 'Reservations');

        // 3. Rooms
        $client->request('GET', '/admin/rooms');
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('h1, h2, h3, .admin-header', 'Rooms');

        // 4. Services
        $client->request('GET', '/admin/services');
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('h1, h2, h3, .admin-header', 'Services');

        // 5. Settings
        $client->request('GET', '/admin/settings');
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('h1, h2, h3, .admin-header', 'Settings');
    }

    public function testReservationStatusUpdate(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $adminRepo = static::getContainer()->get(AdminRepository::class);
        $resRepo = static::getContainer()->get(ReservationRepository::class);

        $admin = $adminRepo->findOneBy([]) ?? $this->createTestAdmin();
        $client->loginUser($admin, 'admin');

        $reservation = $resRepo->findOneBy([]);
        if (!$reservation) {
            $reservation = new Reservation();
            $reservation->setFullName('Admin Test Guest')
                ->setEmail('guest@test.local')
                ->setPhone('+639111111111')
                ->setRoomType('standard')
                ->setCheckinDate(new \DateTime('tomorrow'))
                ->setCheckoutDate(new \DateTime('+3 days'))
                ->setAdults(2)
                ->setChildren(0)
                ->setPaymentMethod('cash')
                ->setTotalAmount(5000.00)
                ->setStatus('pending')
                ->setCreatedAt(new \DateTimeImmutable());
            $em->persist($reservation);
            $em->flush();
        }

        $resId = $reservation->getId();

        $client->request('POST', '/admin/reservations', [
            'update_status' => '1',
            'reservation_id' => $resId,
            'new_status' => 'confirmed',
        ]);

        $this->assertResponseRedirects('/admin/reservations');
        $client->followRedirect();

        $em->clear();
        $updated = $resRepo->find($resId);
        $this->assertNotNull($updated);
        $this->assertSame('confirmed', $updated->getStatus());
    }

    public function testRoomCreationAndModification(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $adminRepo = static::getContainer()->get(AdminRepository::class);
        $roomRepo = static::getContainer()->get(RoomRepository::class);

        $admin = $adminRepo->findOneBy([]) ?? $this->createTestAdmin();
        $client->loginUser($admin, 'admin');

        // Create new room
        $crawler = $client->request('GET', '/admin/rooms');
        $csrfToken = $crawler->filter('input[name="_token"]')->first()->attr('value');
        $testRoomName = 'Automated Test Suite ' . uniqid();
        $client->request('POST', '/admin/rooms', [
            '_token' => $csrfToken,
            'save_room' => '1',
            'edit_id' => 0,
            'name' => $testRoomName,
            'badge' => 'Luxury Test',
            'badge_class' => 'badge-gold',
            'specs' => '2 Guests | 1 King Bed | Ocean View',
            'price' => '8500',
            'description' => 'A wonderful automated test suite room.',
            'image_url' => 'images/rooms/deluxe-garden-1.jpg',
        ]);

        $this->assertResponseRedirects('/admin/rooms');
        $client->followRedirect();

        $em->clear();
        $room = $roomRepo->findOneBy(['name' => $testRoomName]);
        $this->assertNotNull($room, 'Room should have been created in database');
        $this->assertSame(8500.0, (float) $room->getPrice());

        // Update the room
        $client->request('POST', '/admin/rooms', [
            '_token' => $csrfToken,
            'save_room' => '1',
            'edit_id' => $room->getId(),
            'name' => $testRoomName . ' Updated',
            'badge' => 'Luxury Test Updated',
            'badge_class' => 'badge-gold',
            'specs' => '4 Guests | 2 King Beds',
            'price' => '9900',
            'description' => 'Updated test suite description.',
            'image_url' => 'images/rooms/deluxe-garden-1.jpg',
        ]);

        $this->assertResponseRedirects('/admin/rooms');

        $em->clear();
        $updatedRoom = $roomRepo->find($room->getId());
        $this->assertSame($testRoomName . ' Updated', $updatedRoom->getName());
        $this->assertSame(9900.0, (float) $updatedRoom->getPrice());

        // Delete test room to clean up
        $client->request('POST', '/admin/rooms', [
            '_token' => $csrfToken,
            'delete_room' => '1',
            'room_id' => $room->getId(),
        ]);
        $this->assertResponseRedirects('/admin/rooms');
    }

    public function testServiceCreationAndModification(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $adminRepo = static::getContainer()->get(AdminRepository::class);
        $serviceRepo = static::getContainer()->get(ServiceRepository::class);

        $admin = $adminRepo->findOneBy([]) ?? $this->createTestAdmin();
        $client->loginUser($admin, 'admin');

        // Create new service
        $crawler = $client->request('GET', '/admin/services');
        $csrfToken = $crawler->filter('input[name="_token"]')->first()->attr('value');
        $testServiceName = 'Snorkeling Tour ' . uniqid();
        $client->request('POST', '/admin/services', [
            '_token' => $csrfToken,
            'save_service' => '1',
            'edit_id' => 0,
            'category' => 'experience',
            'name' => $testServiceName,
            'tag' => 'Marine Sanctuary',
            'description' => 'Explore the vibrant marine life of Siquijor.',
            'hours' => '8:00 AM – 4:00 PM',
            'price' => '1500',
            'image_url' => 'images/experiences/malaya-tours.jpg',
        ]);

        $this->assertResponseRedirects('/admin/services');
        $client->followRedirect();

        $em->clear();
        $service = $serviceRepo->findOneBy(['name' => $testServiceName]);
        $this->assertNotNull($service, 'Service should have been created in database');
        $this->assertSame(1500.0, (float) $service->getPrice());

        // Clean up
        $client->request('POST', '/admin/services', [
            '_token' => $csrfToken,
            'delete_service' => '1',
            'service_id' => $service->getId(),
        ]);
        $this->assertResponseRedirects('/admin/services');
    }

    private function createTestAdmin(): Admin
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $admin = new Admin();
        $admin->setName('System Admin')
            ->setEmail('admin@sunscape.com')
            ->setPassword(password_hash('admin123', PASSWORD_BCRYPT))
            ->setCreatedAt(new \DateTimeImmutable());
        $em->persist($admin);
        $em->flush();
        return $admin;
    }
}
