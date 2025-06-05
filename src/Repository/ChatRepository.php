<?php

namespace App\Repository;

use App\Entity\Chat;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Chat>
 */
class ChatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Chat::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.messages', 'm')
            ->where('c.userOne = :user OR c.userTwo = :user')
            ->setParameter('user', $user)
            ->orderBy('c.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findBetweenUsers(User $userOne, User $userTwo): ?Chat
    {
        return $this->createQueryBuilder('c')
            ->where('(c.userOne = :userOne AND c.userTwo = :userTwo) OR (c.userOne = :userTwo AND c.userTwo = :userOne)')
            ->setParameter('userOne', $userOne)
            ->setParameter('userTwo', $userTwo)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
