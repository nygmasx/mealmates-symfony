<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findPreviousSellers(User $user): array
    {
        return $this->createQueryBuilder('o')
            ->select('DISTINCT u.id')
            ->join('o.products', 'p')
            ->join('p.user', 'u')
            ->where('o.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}
