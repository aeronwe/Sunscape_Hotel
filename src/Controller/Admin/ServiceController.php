<?php

namespace App\Controller\Admin;

use App\Entity\Service;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class ServiceController extends AbstractController
{
    #[Route('/services', name: 'admin_services', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        ServiceRepository $serviceRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('admin_services', $request->request->getString('_token'))) {
                $this->addFlash('error', 'Invalid security token. Please try again.');
                return $this->redirectToRoute('admin_services');
            }

            if ($request->request->has('save_service')) {
                $editId = $request->request->getInt('edit_id');
                $category = trim($request->request->getString('category'));
                $name = trim($request->request->getString('name'));
                $tag = trim($request->request->getString('tag'));
                $desc = trim($request->request->getString('description'));
                $hours = trim($request->request->getString('hours'));
                $price = (float) $request->request->get('price', 0);
                $imageUrl = trim($request->request->getString('image_url'));

                $allowedCategories = ['dining', 'experience', 'brand'];
                if ($name !== '' && in_array($category, $allowedCategories, true) && $imageUrl !== '') {
                    if ($editId > 0) {
                        $service = $serviceRepository->find($editId);
                        if ($service) {
                            $service->setCategory($category)
                                ->setName($name)
                                ->setTag($tag !== '' ? $tag : null)
                                ->setDescription($desc !== '' ? $desc : null)
                                ->setHours($hours !== '' ? $hours : null)
                                ->setPrice($price)
                                ->setImageUrl($imageUrl);
                            $entityManager->flush();
                            $this->addFlash('success', "Service \"$name\" has been updated.");
                        }
                    } else {
                        $service = new Service();
                        $service->setCategory($category)
                            ->setName($name)
                            ->setTag($tag !== '' ? $tag : null)
                            ->setDescription($desc !== '' ? $desc : null)
                            ->setHours($hours !== '' ? $hours : null)
                            ->setPrice($price)
                            ->setImageUrl($imageUrl);
                        $entityManager->persist($service);
                        $entityManager->flush();
                        $this->addFlash('success', "Service \"$name\" has been added.");
                    }
                } else {
                    $this->addFlash('error', 'Please fill in all required fields (name, category, image).');
                }

                return $this->redirectToRoute('admin_services');
            }

            if ($request->request->has('delete_service')) {
                $serviceId = $request->request->getInt('service_id');
                $service = $serviceRepository->find($serviceId);
                if ($service) {
                    $entityManager->remove($service);
                    $entityManager->flush();
                    $this->addFlash('success', 'Service has been deleted.');
                }
                return $this->redirectToRoute('admin_services');
            }
        }

        $allServices = $serviceRepository->findAll();

        $grouped = [
            'dining' => [],
            'experience' => [],
            'brand' => [],
        ];
        foreach ($allServices as $svc) {
            $cat = $svc->getCategory();
            if (isset($grouped[$cat])) {
                $grouped[$cat][] = $svc;
            } else {
                $grouped[$cat] = [$svc];
            }
        }

        return $this->render('admin/services.html.twig', [
            'allServices' => $allServices,
            'grouped' => $grouped,
            'current_page' => 'services',
        ]);
    }
}
