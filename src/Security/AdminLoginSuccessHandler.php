<?php

namespace App\Security;

use App\Entity\Admin;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

final class AdminLoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): ?Response
    {
        $user = $token->getUser();

        if ($user instanceof Admin && $user->isStaff() && !$user->isAdmin()) {
            return new RedirectResponse($this->urlGenerator->generate('admin_rooms'));
        }

        return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
    }
}

