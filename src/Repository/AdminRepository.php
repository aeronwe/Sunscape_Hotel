<?php

namespace App\Repository;

use App\Entity\Admin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<Admin>
 */
class AdminRepository extends ServiceEntityRepository implements PasswordUpgraderInterface, UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Admin::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Admin) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function loadUserByIdentifier(string $identifier): ?UserInterface
    {
        return $this->findByIdentifier($identifier);
    }

    public function findByIdentifier(string $identifier): ?Admin
    {
        $identifier = trim($identifier);
        return $this->createQueryBuilder('a')
            ->where('LOWER(a.email) = LOWER(:id)')
            ->orWhere('LOWER(a.name) = LOWER(:id)')
            ->setParameter('id', $identifier)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Admin[]
     */
    public function findAllFiltered(?string $role = null, ?string $status = null, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC');

        if ($role && $role !== 'all') {
            $qb->andWhere('a.role = :role')
               ->setParameter('role', $role);
        }

        if ($status && $status !== 'all') {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $status);
        }

        if ($search && trim($search) !== '') {
            $qb->andWhere('LOWER(a.name) LIKE LOWER(:search) OR LOWER(a.email) LIKE LOWER(:search)')
               ->setParameter('search', '%' . trim($search) . '%');
        }

        return $qb->getQuery()->getResult();
    }
}
