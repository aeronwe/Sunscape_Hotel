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
            '/admin/staff',
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
        $admin = $adminRepo->findOneBy(['role' => Admin::ROLE_ADMIN]) ?? $this->createTestAdmin();

        $client->loginUser($admin, 'admin');

        // 1. Dashboard
        $client->request('GET', '/admin');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.admin-sidebar');
        $this->assertAnySelectorTextContains('h1, h2, h3, .admin-header', 'Dashboard');

        // 2. Staff Management
        $client->request('GET', '/admin/staff');
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('h1, h2, h3, .admin-header', 'Staff');

        // 3. Reservations
        $client->request('GET', '/admin/reservations');
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('h1, h2, h3, .admin-header', 'Reservations');

        // 4. Rooms
        $client->request('GET', '/admin/rooms');
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('h1, h2, h3, .admin-header', 'Rooms');

        // 5. Services
        $client->request('GET', '/admin/services');
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('h1, h2, h3, .admin-header', 'Services');

        // 6. Settings
        $client->request('GET', '/admin/settings');
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('h1, h2, h3, .admin-header', 'Settings');
    }

    public function testStaffAccessRestrictionsAndRecordOwnership(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = static::getContainer()->get(RoomRepository::class);

        $admin = $this->createTestAdmin('owner_admin_' . uniqid() . '@sunscape.com', Admin::ROLE_ADMIN);
        $staff = $this->createTestAdmin('staff_user_' . uniqid() . '@sunscape.com', Admin::ROLE_STAFF);

        // Create a room owned by Admin
        $adminRoom = new Room();
        $adminRoom->setName('Admin Owned Room ' . uniqid())
            ->setBadge('Suite')
            ->setBadgeClass('suite')
            ->setSpecs('50 sqm · 2 Guests')
            ->setPrice(9500)
            ->setImageUrl('images/rooms/suite.jpg')
            ->setCreatedBy($admin);
        $em->persist($adminRoom);
        $em->flush();

        $client->loginUser($staff, 'admin');

        // Staff must be blocked (403) from Admin Dashboard (/admin) and Staff Management (/admin/staff)
        $client->request('GET', '/admin');
        $this->assertResponseStatusCodeSame(403);

        $client->request('GET', '/admin/staff');
        $this->assertResponseStatusCodeSame(403);

        // Staff can access /admin/rooms and create their own room
        $crawler = $client->request('GET', '/admin/rooms');
        $this->assertResponseIsSuccessful();
        $csrfToken = $crawler->filter('input[name="_token"]')->first()->attr('value');

        $staffRoomName = 'Staff Owned Room ' . uniqid();
        $client->request('POST', '/admin/rooms', [
            '_token' => $csrfToken,
            'save_room' => '1',
            'edit_id' => 0,
            'name' => $staffRoomName,
            'badge' => 'Standard',
            'badge_class' => 'standard',
            'specs' => '30 sqm · 2 Guests',
            'price' => '3500',
            'description' => 'Created by staff',
            'image_url' => 'images/rooms/standard.jpg',
        ]);
        $this->assertResponseRedirects('/admin/rooms');

        // Staff cannot edit or delete Admin's room (403 Access Denied)
        $client->request('POST', '/admin/rooms', [
            '_token' => $csrfToken,
            'delete_room' => '1',
            'room_id' => $adminRoom->getId(),
        ]);
        $this->assertResponseStatusCodeSame(403);

        // Clean up
        $em->clear();
        $staffRoom = $roomRepo->findOneBy(['name' => $staffRoomName]);
        if ($staffRoom) {
            $em->remove($staffRoom);
        }
        $adminRoomReloaded = $roomRepo->find($adminRoom->getId());
        if ($adminRoomReloaded) {
            $em->remove($adminRoomReloaded);
        }
        $em->flush();
    }

    public function testReservationStatusUpdate(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $adminRepo = static::getContainer()->get(AdminRepository::class);
        $resRepo = static::getContainer()->get(ReservationRepository::class);

        $admin = $adminRepo->findOneBy(['role' => Admin::ROLE_ADMIN]) ?? $this->createTestAdmin();
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
        $crawler = $client->request('GET', '/admin/reservations');
        $csrfToken = $crawler->filter('input[name="_token"]')->first()->attr('value');

        $client->request('POST', '/admin/reservations', [
            '_token' => $csrfToken,
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

        $admin = $adminRepo->findOneBy(['role' => Admin::ROLE_ADMIN]) ?? $this->createTestAdmin();
        $client->loginUser($admin, 'admin');

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

        $admin = $adminRepo->findOneBy(['role' => Admin::ROLE_ADMIN]) ?? $this->createTestAdmin();
        $client->loginUser($admin, 'admin');

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

        $client->request('POST', '/admin/services', [
            '_token' => $csrfToken,
            'delete_service' => '1',
            'service_id' => $service->getId(),
        ]);
        $this->assertResponseRedirects('/admin/services');
    }

    private function createTestAdmin(string $email = 'admin@sunscape.com', string $role = Admin::ROLE_ADMIN): Admin
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $admin = new Admin();
        $admin->setName($role === Admin::ROLE_ADMIN ? 'System Admin' : 'Resort Staff')
            ->setEmail($email)
            ->setRole($role)
            ->setStatus(Admin::STATUS_ACTIVE)
            ->setPassword(password_hash('admin123', PASSWORD_BCRYPT))
            ->setCreatedAt(new \DateTimeImmutable());
        $em->persist($admin);
        $em->flush();
        return $admin;
    }
}

