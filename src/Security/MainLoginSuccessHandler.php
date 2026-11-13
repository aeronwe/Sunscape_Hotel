<?php

namespace App\Security;

use App\Entity\Admin;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

final class MainLoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): ?Response
    {
        $user = $token->getUser();

        if ($user instanceof Admin) {
            $route = $user->isAdmin() ? 'admin_dashboard' : 'admin_rooms';
        } else {
            $route = $request->query->getString('redirect') === 'booking' ? 'booking_index' : 'account_index';
        }

        return new RedirectResponse($this->urlGenerator->generate($route));
    }
}
