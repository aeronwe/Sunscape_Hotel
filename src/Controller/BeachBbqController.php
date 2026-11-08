<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BeachBbqController extends AbstractController
{
    #[Route('/beach-bbq', name: 'beach_bbq', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('brand/beach_bbq.html.twig');
    }
}

