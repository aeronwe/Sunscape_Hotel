<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MalayaToursController extends AbstractController
{
    #[Route('/malaya-tours', name: 'malaya_tours', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('brand/malaya_tours.html.twig');
    }
}

