<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    /**
     * @return Booking[] Returns an array of Booking objects where user is either buyer or seller
     */
    public function findAllBookingsForUser($user): array
    {
        $buyerBookings = $this->findBy(['user' => $user]);

        $sellerBookings = $this->createQueryBuilder('b')
            ->join('b.product', 'p')
            ->where('p.user = :seller')
            ->setParameter('seller', $user)
            ->getQuery()
            ->getResult();

        return array_merge($buyerBookings, $sellerBookings);
    }

    /**
     * @return Booking[] Returns an array of Booking objects where user is the seller (product owner)
     */
    public function findBookingsForSeller(User $user): array
    {
        return $this->createQueryBuilder('b')
            ->join('b.product', 'p')
            ->where('p.user = :seller')
            ->setParameter('seller', $user->getId(), UuidType::NAME)
            ->getQuery()
            ->getResult();
    }
}
