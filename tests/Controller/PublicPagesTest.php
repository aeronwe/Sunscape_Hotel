<?php

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PublicPagesTest extends WebTestCase
{
    #[DataProvider('publicUrlProvider')]
    public function testPublicPageIsSuccessful(string $url, string $expectedHeading): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', $url);

        $this->assertResponseIsSuccessful(sprintf('Failed to load %s', $url));
        if ($expectedHeading !== '') {
            $this->assertStringContainsString($expectedHeading, $client->getResponse()->getContent());
        }
    }

    public static function publicUrlProvider(): array
    {
        return [
            'Home page' => ['/', 'Ejercito'],
            'Rooms list' => ['/rooms', 'Rooms &amp; Suites'],
            'Room detail (standard twin)' => ['/rooms/standard-twin', 'Standard Twin Room'],
            'Room detail (deluxe garden)' => ['/rooms/deluxe-garden', 'Deluxe Garden View'],
            'Room detail (premier ocean)' => ['/rooms/premier-ocean', 'Premier Ocean Suite'],
            'Dining list' => ['/dining', 'Dining &amp; Cuisine'],
            'Dining detail (el-juwan)' => ['/dining/el-juwan', 'El Juwan Restaurant'],
            'Dining detail (island-bar)' => ['/dining/island-bar', 'The Island Bar'],
            'Dining detail (coast-grill)' => ['/dining/coast-grill', 'Coast Grill Nights'],
            'Experiences list' => ['/experiences', 'Curated Island Experiences'],
            'El Juwan page' => ['/el-juwan', 'El Juwan Restaurant'],
            'Island Bar page' => ['/island-bar', 'The Island Bar'],
            'Beach BBQ page' => ['/beach-bbq', 'Coast Grill Nights'],
            'Malaya Tours page' => ['/malaya-tours', 'Malaya Tours'],
            'Contact page' => ['/contact', 'Contact Us'],
            'Login page' => ['/login', 'Welcome Back'],
            'Register page' => ['/register', 'Sign Up'],
            'Admin login page' => ['/admin/login', 'Staff &amp; Management'],
        ];
    }

    public function testContactFormSubmission(): void
    {
        $client = static::createClient();
        $client->request('POST', '/contact', [
            'contact_name' => 'John Doe',
            'contact_email' => 'john.doe@example.com',
            'contact_phone' => '+63 912 345 6789',
            'contact_subject' => 'General Inquiry',
            'contact_message' => 'Hello, I have a question about booking an ocean suite for a wedding anniversary.',
        ]);

        $this->assertResponseRedirects('/contact');
        $client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'Thank you for your message');
    }

    public function testMobileNavigationIncludesAccountLoginLink(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('.mobile-menu-header'));
        $this->assertSame('mainNavigation', $crawler->filter('#menuToggle')->attr('aria-controls'));
        $this->assertSame('false', $crawler->filter('#menuToggle')->attr('aria-expanded'));
        $this->assertSame('mobileMenuClose', $crawler->filter('.mobile-menu-close')->attr('id'));
        $this->assertSame('Ejercito\'s Sunscape Resort logo', $crawler->filter('.mobile-menu-brand img')->attr('alt'));
        $this->assertSame(
            '/login',
            $crawler->filter('.navbar-mobile-account a')->attr('href'),
        );
    }

    public function testHomepageDoesNotShowUnrelatedBoholSeaCafe(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('Bohol Sea Cafe', $client->getResponse()->getContent());
    }
}
