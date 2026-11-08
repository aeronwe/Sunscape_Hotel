<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IslandBarController extends AbstractController
{
    #[Route('/island-bar', name: 'island_bar', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('brand/island_bar.html.twig');
    }
}

