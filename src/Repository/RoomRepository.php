<?php

namespace App\Repository;

use App\Entity\Room;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Room>
 */
class RoomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Room::class);
    }

    /**
     * Returns the first $limit rooms ordered by creation date (oldest first),
     * matching the legacy homepage "featured rooms" behaviour.
     *
     * @return Room[]
     */
    public function findFeatured(int $limit = 3): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a single room whose derived slug matches $slug.
     * Since the slug is computed from the name, we fetch all and filter in PHP
     * (the rooms table is small; add a slug column later for large datasets).
     */
    public function findBySlug(string $slug): ?Room
    {
        $rooms = $this->findAll();
        foreach ($rooms as $room) {
            if ($room->getSlug() === $slug) {
                return $room;
            }
        }

        return null;
    }
}

