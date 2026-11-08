<?php

namespace App\Repository;

use App\Entity\ReservationService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReservationService>
 */
class ReservationServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReservationService::class);
    }

    /**
     * @return ReservationService[]
     */
    public function findByReservationId(int $reservationId): array
    {
        return $this->createQueryBuilder('rs')
            ->innerJoin('rs.service', 's')
            ->addSelect('s')
            ->where('rs.reservation = :reservationId')
            ->setParameter('reservationId', $reservationId)
            ->getQuery()
            ->getResult();
    }
}

