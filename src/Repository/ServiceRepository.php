<?php

namespace App\Repository;

use App\Entity\Admin;
use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Service>
 */
class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }

    /**
     * @return Service[]
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.category = :category')
            ->setParameter('category', $category)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Service[]
     */
    public function findAllFiltered(?string $category = null, ?string $search = null, ?string $ownerFilter = null, ?Admin $currentAdmin = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.createdBy', 'c')
            ->addSelect('c')
            ->orderBy('s.id', 'ASC');

        if ($category && $category !== 'all') {
            $qb->andWhere('s.category = :category')
               ->setParameter('category', $category);
        }

        if ($search && trim($search) !== '') {
            $qb->andWhere('LOWER(s.name) LIKE LOWER(:search) OR LOWER(s.tag) LIKE LOWER(:search) OR LOWER(s.description) LIKE LOWER(:search)')
               ->setParameter('search', '%' . trim($search) . '%');
        }

        if ($ownerFilter === 'mine' && $currentAdmin) {
            $qb->andWhere('s.createdBy = :creator')
               ->setParameter('creator', $currentAdmin);
        }

        return $qb->getQuery()->getResult();
    }
}
