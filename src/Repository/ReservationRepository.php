<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * @return Reservation[]
     */
    public function findByUserId(int $userId): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.room', 'rm')
            ->addSelect('rm')
            ->where('r.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Alias used by AccountController.
     * @return Reservation[]
     */
    public function findForUser(\App\Entity\User $user): array
    {
        return $this->findByUserId((int) $user->getId());
    }

    /**
     * @return Reservation[]
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.room', 'rm')
            ->addSelect('rm')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Reservation[]
     */
    public function findAllFiltered(?string $status = null, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.room', 'rm')
            ->addSelect('rm')
            ->orderBy('r.createdAt', 'DESC');

        if ($status && $status !== 'all') {
            $qb->andWhere('r.status = :status')
               ->setParameter('status', $status);
        }

        if ($search && trim($search) !== '') {
            $qb->andWhere('r.fullName LIKE :search OR r.email LIKE :search OR r.roomType LIKE :search')
               ->setParameter('search', '%' . trim($search) . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function countPending(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.status = :status')
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countTodayCheckins(\DateTimeInterface $today): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.checkinDate = :today')
            ->andWhere('r.status != :cancelled')
            ->setParameter('today', $today->format('Y-m-d'))
            ->setParameter('cancelled', 'cancelled')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function sumConfirmedRevenue(): float
    {
        $val = $this->createQueryBuilder('r')
            ->select('SUM(r.totalAmount)')
            ->where('r.status = :status')
            ->setParameter('status', 'confirmed')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($val ?? 0);
    }
}
