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
            ->where('c.userOne = :user OR c.userTwo = :user')
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
