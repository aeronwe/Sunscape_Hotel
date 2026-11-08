<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ElJuwanController extends AbstractController
{
    #[Route('/el-juwan', name: 'el_juwan', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('brand/el_juwan.html.twig');
    }
}

