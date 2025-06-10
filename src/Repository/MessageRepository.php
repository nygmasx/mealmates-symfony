<?php

namespace App\Repository;

use App\Entity\Chat;
use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function findNewMessages(Chat $chat, string $afterId): array
    {
        try {
            $afterUuid = Uuid::fromString($afterId);

            $afterMessage = $this->find($afterUuid);
            if (!$afterMessage) {
                return [];
            }

            return $this->createQueryBuilder('m')
                ->where('m.chat = :chat')
                ->andWhere('m.isDeleted = false')
                ->andWhere('m.createdAt > :afterDate')
                ->setParameter('chat', $chat)
                ->setParameter('afterDate', $afterMessage->getCreatedAt())
                ->orderBy('m.createdAt', 'ASC')
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            return [];
        }
    }
}
