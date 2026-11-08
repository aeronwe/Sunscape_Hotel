<?php

namespace App\Controller\Admin;

use App\Entity\Room;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class RoomController extends AbstractController
{
    #[Route('/rooms', name: 'admin_rooms', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        RoomRepository $roomRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('admin_rooms', $request->request->getString('_token'))) {
                $this->addFlash('error', 'Invalid security token. Please try again.');
                return $this->redirectToRoute('admin_rooms');
            }

            if ($request->request->has('save_room')) {
                $editId = $request->request->getInt('edit_id');
                $name = trim($request->request->getString('name'));
                $badge = trim($request->request->getString('badge'));
                $badgeClass = trim($request->request->getString('badge_class'));
                $specs = trim($request->request->getString('specs'));
                $price = (float) $request->request->get('price', 0);
                $desc = trim($request->request->getString('description'));
                $imageUrl = trim($request->request->getString('image_url'));

                if ($name !== '' && $badge !== '' && $specs !== '' && $price > 0 && $imageUrl !== '') {
                    if ($editId > 0) {
                        $room = $roomRepository->find($editId);
                        if ($room) {
                            $room->setName($name)
                                ->setBadge($badge)
                                ->setBadgeClass($badgeClass)
                                ->setSpecs($specs)
                                ->setPrice($price)
                                ->setDescription($desc !== '' ? $desc : null)
                                ->setImageUrl($imageUrl);
                            $entityManager->flush();
                            $this->addFlash('success', "Room \"$name\" has been updated.");
                        }
                    } else {
                        $room = new Room();
                        $room->setName($name)
                            ->setBadge($badge)
                            ->setBadgeClass($badgeClass)
                            ->setSpecs($specs)
                            ->setPrice($price)
                            ->setDescription($desc !== '' ? $desc : null)
                            ->setImageUrl($imageUrl);
                        $entityManager->persist($room);
                        $entityManager->flush();
                        $this->addFlash('success', "Room \"$name\" has been added.");
                    }
                } else {
                    $this->addFlash('error', 'Please fill in all required fields.');
                }

                return $this->redirectToRoute('admin_rooms');
            }

            if ($request->request->has('delete_room')) {
                $roomId = $request->request->getInt('room_id');
                $room = $roomRepository->find($roomId);
                if ($room) {
                    $entityManager->remove($room);
                    $entityManager->flush();
                    $this->addFlash('success', 'Room has been deleted.');
                }
                return $this->redirectToRoute('admin_rooms');
            }
        }

        $rooms = $roomRepository->findAll();

        return $this->render('admin/rooms.html.twig', [
            'rooms' => $rooms,
            'current_page' => 'rooms',
        ]);
    }
}
