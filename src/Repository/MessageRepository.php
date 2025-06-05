<?php

namespace App\Repository;

use App\Entity\Chat;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function findByChatOrderedByDate(Chat $chat, int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.chat = :chat')
            ->andWhere('m.isDeleted = false')
            ->setParameter('chat', $chat)
            ->orderBy('m.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getUnreadCount(Chat $chat, User $user): int
    {
        $lastSeenAt = $chat->getUserOne() === $user
            ? $chat->getUserOneLastSeenAt()
            : $chat->getUserTwoLastSeenAt();

        $qb = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.chat = :chat')
            ->andWhere('m.sender != :user')
            ->andWhere('m.isDeleted = false')
            ->setParameter('chat', $chat)
            ->setParameter('user', $user);

        if ($lastSeenAt) {
            $qb->andWhere('m.createdAt > :lastSeen')
                ->setParameter('lastSeen', $lastSeenAt);
        }

        return (int)$qb->getQuery()->getSingleScalarResult();
    }
}
