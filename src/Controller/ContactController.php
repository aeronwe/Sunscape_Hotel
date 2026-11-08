<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $name    = trim($request->request->getString('contact_name'));
            $email   = trim($request->request->getString('contact_email'));
            $phone   = trim($request->request->getString('contact_phone'));
            $subject = trim($request->request->getString('contact_subject'));
            $message = trim($request->request->getString('contact_message'));

            if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && $message) {
                $this->addFlash('success', "Thank you for your message! We'll get back to you within 24 hours.");
                return $this->redirectToRoute('contact_index');
            }

            $this->addFlash('error', 'Please complete all required fields with a valid email address.');
        }

        return $this->render('contact/index.html.twig');
    }
}
