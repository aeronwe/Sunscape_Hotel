<?php

namespace App\Tests\Controller;

use App\Entity\Admin;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityControllerTest extends WebTestCase
{
    public function testMainLoginUsesOneFormForGuestsAndStaff(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSame('/login?redirect=', $crawler->filter('#loginForm')->attr('action'));
        $this->assertSame('text', $crawler->filter('#email')->attr('type'));
        $this->assertCount(0, $crawler->filter('.auth-admin-link'));
    }

    public function testAdminAndStaffCanSignInThroughMainLogin(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        foreach ([Admin::ROLE_ADMIN => '/admin', Admin::ROLE_STAFF => '/admin/rooms'] as $role => $destination) {
            $admin = new Admin();
            $admin->setName('Login Test')
                ->setEmail('login_' . uniqid() . '@example.com')
                ->setRole($role)
                ->setPassword($hasher->hashPassword($admin, 'TestPassword123!'));
            $em->persist($admin);
            $em->flush();

            $crawler = $client->request('GET', '/login');
            $client->request('POST', '/login', [
                '_csrf_token' => $crawler->filter('input[name="_csrf_token"]')->attr('value'),
                'email' => $admin->getEmail(),
                'password' => 'TestPassword123!',
            ]);

            $this->assertResponseRedirects($destination);
            $client->followRedirect();
            $this->assertResponseIsSuccessful();

            $client->request('GET', '/account');
            $this->assertResponseRedirects($destination);
            $client->request('GET', '/booking');
            $this->assertResponseRedirects($destination);

            $adminId = $admin->getId();
            $em = static::getContainer()->get(EntityManagerInterface::class);
            $em->remove($em->find(Admin::class, $adminId));
            $em->flush();
            $client->restart();
        }
    }

    public function testDisabledStaffCannotSignInThroughMainLogin(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $admin = new Admin();
        $admin->setName('Disabled Login Test')
            ->setEmail('disabled_login_' . uniqid() . '@example.com')
            ->setRole(Admin::ROLE_STAFF)
            ->setStatus(Admin::STATUS_DISABLED)
            ->setPassword($hasher->hashPassword($admin, 'TestPassword123!'));
        $em->persist($admin);
        $em->flush();

        $crawler = $client->request('GET', '/login');
        $client->request('POST', '/login', [
            '_csrf_token' => $crawler->filter('input[name="_csrf_token"]')->attr('value'),
            'email' => $admin->getEmail(),
            'password' => 'TestPassword123!',
        ]);

        $this->assertResponseRedirects('/login');
        $client->followRedirect();
        $this->assertAnySelectorTextContains('.auth-error-text', 'disabled');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->remove($em->find(Admin::class, $admin->getId()));
        $em->flush();
    }

    public function testRegistrationFlow(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $userRepo = static::getContainer()->get(UserRepository::class);

        // 1. GET /register
        $client->request('GET', '/register');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');

        // 2. POST /register with mismatched passwords
        $testEmail = 'newguest_' . uniqid() . '@example.com';
        $client->request('POST', '/register', [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => $testEmail,
            'password' => 'SecurePass123!',
            'confirm_password' => 'DifferentPass456!',
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.auth-error-text');

        // 3. POST /register with valid information
        $client->request('POST', '/register', [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => $testEmail,
            'password' => 'SecurePass123!',
            'confirm_password' => 'SecurePass123!',
        ]);

        $this->assertResponseRedirects('/login');
        $client->followRedirect();
        $this->assertSelectorExists('.auth-flash-success');

        // Verify user in database
        $em->clear();
        $createdUser = $userRepo->findOneBy(['email' => $testEmail]);
        $this->assertNotNull($createdUser);
        $this->assertSame('Maria', $createdUser->getFirstName());
        $this->assertSame('Santos', $createdUser->getLastName());
    }

    public function testLoginAndAccountAccessFlow(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $userRepo = static::getContainer()->get(UserRepository::class);

        // Create a verified test user
        $testEmail = 'verified_' . uniqid() . '@example.com';
        $user = new User();
        $user->setFirstName('Pedro')
            ->setLastName('Penduko')
            ->setEmail($testEmail)
            ->setPassword($hasher->hashPassword($user, 'SecretPassword123!'))
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());
        $em->persist($user);
        $em->flush();

        // 1. Unauthenticated /account redirects to /login
        $client->request('GET', '/account');
        $this->assertResponseRedirects('/login');

        // 2. Perform form login
        $crawler = $client->request('GET', '/login');
        $csrfToken = $crawler->filter('input[name="_csrf_token"]')->attr('value');
        $client->request('POST', '/login', [
            '_csrf_token' => $csrfToken,
            'email' => $testEmail,
            'password' => 'SecretPassword123!',
        ]);

        $this->assertResponseRedirects('/account');
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('h1, h2, h3, .account-hero, .account-title', 'Pedro');

        // 3. Update profile via POST /account
        $crawler = $client->request('GET', '/account');
        $accountToken = $crawler->filter('input[name="_token"]')->first()->attr('value');
        $client->request('POST', '/account', [
            '_token' => $accountToken,
            'update_profile' => '1',
            'first_name' => 'Pedro Updated',
            'last_name' => 'Penduko Senior',
            'email' => $testEmail,
            'current_password' => '',
            'new_password' => '',
            'confirm_password' => '',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.alert-success');

        $em->clear();
        $updatedUser = $userRepo->findOneBy(['email' => $testEmail]);
        $this->assertSame('Pedro Updated', $updatedUser->getFirstName());
        $this->assertSame('Penduko Senior', $updatedUser->getLastName());

        // 4. Test change-own-password with wrong current password fails
        $crawler = $client->request('GET', '/account');
        $accountToken = $crawler->filter('input[name="_token"]')->first()->attr('value');
        $client->request('POST', '/account', [
            '_token' => $accountToken,
            'change_password' => '1',
            'current_password' => 'WrongCurrentPassword!',
            'new_password' => 'BrandNewPass456!',
            'confirm_password' => 'BrandNewPass456!',
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.alert-error');

        // 5. Test change-own-password with valid current password succeeds
        $client->request('POST', '/account', [
            '_token' => $accountToken,
            'change_password' => '1',
            'current_password' => 'SecretPassword123!',
            'new_password' => 'BrandNewPass456!',
            'confirm_password' => 'BrandNewPass456!',
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.alert-success');

        // 6. Test logout
        $crawler = $client->request('GET', '/account');
        $logoutToken = $crawler->filter('input[name="_csrf_token"]')->attr('value');
        $client->request('POST', '/logout', [
            '_csrf_token' => $logoutToken,
        ]);
        $this->assertResponseRedirects();

        // 7. Prove old password no longer works
        $crawler = $client->request('GET', '/login');
        $loginCsrf = $crawler->filter('input[name="_csrf_token"]')->attr('value');
        $client->request('POST', '/login', [
            '_csrf_token' => $loginCsrf,
            'email' => $testEmail,
            'password' => 'SecretPassword123!',
        ]);
        $this->assertResponseRedirects('/login');
        $client->followRedirect();
        $this->assertSelectorExists('.auth-error-text');

        // 8. Prove new password works
        $crawler = $client->request('GET', '/login');
        $loginCsrf = $crawler->filter('input[name="_csrf_token"]')->attr('value');
        $client->request('POST', '/login', [
            '_csrf_token' => $loginCsrf,
            'email' => $testEmail,
            'password' => 'BrandNewPass456!',
        ]);
        $this->assertResponseRedirects('/account');
        $client->followRedirect();
        $this->assertResponseIsSuccessful();

        // 9. Test regular user account deletion
        $crawler = $client->request('GET', '/account');
        $accountToken = $crawler->filter('input[name="_token"]')->first()->attr('value');
        $client->request('POST', '/account', [
            '_token' => $accountToken,
            'delete_account' => '1',
        ]);
        $this->assertResponseRedirects('/login');

        $em->clear();
        $deletedUser = $userRepo->findOneBy(['email' => $testEmail]);
        $this->assertNull($deletedUser, 'Deleted user should no longer exist in the database.');
    }
}
