<?php

namespace App\Controller;

use App\Entity\Chat;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\ChatRepository;
use App\Repository\MessageRepository;
use App\Security\Voter\ChatVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/chat')]
#[IsGranted('ROLE_USER')]
class ChatController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SerializerInterface $serializer,
        private readonly ChatRepository $chatRepository,
        private readonly MessageRepository $messageRepository
    ) {}

    #[Route('/list', name: 'chat_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $chats = $this->chatRepository->findByUser($user);

        return $this->json($chats, Response::HTTP_OK, [], ['groups' => 'chat:list']);
    }

    #[Route('/{id}/messages', name: 'chat_messages', methods: ['GET'])]
    public function getMessages(Chat $chat, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(ChatVoter::CHAT_ACCESS, $chat);

        $afterId = $request->query->get('after');

        if ($afterId) {
            $messages = $this->messageRepository->findNewMessages($chat, $afterId);
        } else {
            $messages = $this->messageRepository->findBy(
                ['chat' => $chat, 'isDeleted' => false],
                ['createdAt' => 'ASC']
            );
        }

        return $this->json($messages, Response::HTTP_OK, [], ['groups' => 'message:read']);
    }

    #[Route('/{id}/messages', name: 'chat_send_message', methods: ['POST'])]
    public function sendMessage(Chat $chat, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(ChatVoter::CHAT_ACCESS, $chat);

        $data = json_decode($request->getContent(), true);

        /** @var User $user */
        $user = $this->getUser();

        $message = new Message();
        $message->setContent($data['content']);
        $message->setType($data['type'] ?? 'text');
        $message->setAttachments($data['attachments'] ?? null);
        $message->setChat($chat);
        $message->setSender($user);
        $message->setCreatedAt(new \DateTimeImmutable());
        $message->setIsDeleted(false);

        $this->entityManager->persist($message);

        $chat->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        return $this->json($message, Response::HTTP_CREATED, [], ['groups' => 'message:read']);
    }

    #[Route('/{id}/mark-read', name: 'chat_mark_read', methods: ['POST'])]
    public function markAsRead(Chat $chat): JsonResponse
    {
        $this->denyAccessUnlessGranted(ChatVoter::CHAT_ACCESS, $chat);

        /** @var User $user */
        $user = $this->getUser();
        $now = new \DateTimeImmutable();

        if ($chat->getUserOne() === $user) {
            $chat->setUserOneLastSeenAt($now);
        } elseif ($chat->getUserTwo() === $user) {
            $chat->setUserTwoLastSeenAt($now);
        }

        $this->entityManager->flush();

        return $this->json(['status' => 'success']);
    }

    #[Route('/unread-counts', name: 'chat_unread_counts', methods: ['GET'])]
    public function getUnreadCounts(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $unreadCounts = $this->chatRepository->getUnreadCounts($user);

        return $this->json($unreadCounts);
    }
}
