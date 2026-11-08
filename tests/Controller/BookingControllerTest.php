<?php

namespace App\Tests\Controller;

use App\Entity\Room;
use App\Entity\Service;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookingControllerTest extends WebTestCase
{
    public function testBookingRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/booking');

        $this->assertResponseRedirects('/login?redirect=booking');
    }

    public function testBookingPageRendersForAuthenticatedUser(): void
    {
        $client = static::createClient();
        $userRepo = static::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'aironjejercito@gmail.com']);
        $this->assertNotNull($user, 'Test user must exist in the database');

        $client->loginUser($user);
        $crawler = $client->request('GET', '/booking');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Make a Reservation');

        // Check legacy markup parity
        $this->assertCount(1, $crawler->filter('#bookCheckin'));
        $this->assertCount(1, $crawler->filter('#bookCheckout'));
        $this->assertCount(1, $crawler->filter('#bookAdults'));
        $this->assertCount(1, $crawler->filter('#bookChildren'));
        $this->assertCount(1, $crawler->filter('#bookRoomType'));
        $this->assertCount(1, $crawler->filter('#summaryRoomImage'));
        $this->assertCount(1, $crawler->filter('#summaryDates'));
        $this->assertCount(1, $crawler->filter('#summaryTotal'));
        $this->assertCount(1, $crawler->filter('#imageLightbox'));
        $this->assertCount(1, $crawler->filter('#bookingModalOverlay'));
        $this->assertCount(1, $crawler->filter('#btnConfirmBooking'));
    }

    public function testApiBookingProcess(): void
    {
        $client = static::createClient();
        $userRepo = static::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'aironjejercito@gmail.com']);
        $this->assertNotNull($user);

        $client->loginUser($user);
        $client->request(
            'POST',
            '/api/booking',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'full_name' => 'Airon Ejercito',
                'email' => 'aironjejercito@gmail.com',
                'phone' => '+63 912 345 6789',
                'room_type' => 'Standard Twin Room',
                'room_id' => 1,
                'checkin_date' => '2026-12-01',
                'checkout_date' => '2026-12-04',
                'adults' => 2,
                'children' => 0,
                'additional_guests' => ['Jane Doe'],
                'payment_method' => 'credit_card',
                'special_requests' => 'Quiet room please',
                'experiences' => [],
                'total_amount' => 11256,
            ])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('reservation_id', $data);
    }
}

