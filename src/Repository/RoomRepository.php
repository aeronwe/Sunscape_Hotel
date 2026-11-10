<?php

namespace App\Repository;

use App\Entity\Admin;
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

    /**
     * @return Room[]
     */
    public function findAllFiltered(?string $search = null, ?string $ownerFilter = null, ?Admin $currentAdmin = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.createdBy', 'c')
            ->addSelect('c')
            ->orderBy('r.id', 'ASC');

        if ($search && trim($search) !== '') {
            $qb->andWhere('LOWER(r.name) LIKE LOWER(:search) OR LOWER(r.badge) LIKE LOWER(:search) OR LOWER(r.specs) LIKE LOWER(:search)')
               ->setParameter('search', '%' . trim($search) . '%');
        }

        if ($ownerFilter === 'mine' && $currentAdmin) {
            $qb->andWhere('r.createdBy = :creator')
               ->setParameter('creator', $currentAdmin);
        }

        return $qb->getQuery()->getResult();
    }
}
