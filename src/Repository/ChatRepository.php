<?php

namespace App\Repository;

use App\Entity\Chat;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;

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
            ->leftJoin('c.userOne', 'u1')
            ->leftJoin('c.userTwo', 'u2')
            ->leftJoin('c.relatedProduct', 'p')
            ->addSelect('u1', 'u2', 'p') // Eager load the relations
            ->where('IDENTITY(c.userOne) = :userId OR IDENTITY(c.userTwo) = :userId')
            ->setParameter('userId', $user->getId(), UuidType::NAME)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getUnreadCounts(User $user): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c.id as chatId')
            ->addSelect('COUNT(m.id) as unreadCount')
            ->leftJoin('c.messages', 'm')
            ->where('c.userOne = :user OR c.userTwo = :user')
            ->andWhere('m.isDeleted = false')
            ->setParameter('user', $user)
            ->groupBy('c.id');

        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->andX(
                    'c.userOne = :user',
                    $qb->expr()->orX(
                        'c.userOneLastSeenAt IS NULL',
                        'm.createdAt > c.userOneLastSeenAt'
                    )
                ),
                $qb->expr()->andX(
                    'c.userTwo = :user',
                    $qb->expr()->orX(
                        'c.userTwoLastSeenAt IS NULL',
                        'm.createdAt > c.userTwoLastSeenAt'
                    )
                )
            )
        );

        $results = $qb->getQuery()->getResult();

        $counts = [];
        foreach ($results as $result) {
            $counts[$result['chatId']] = (int) $result['unreadCount'];
        }

        return $counts;
    }
}
