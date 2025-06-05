<?php

namespace App\Controller;

use App\Entity\Chat;
use App\Entity\Message;
use App\Repository\ChatRepository;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/chat')]
#[IsGranted('ROLE_USER')]
class ChatController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SerializerInterface    $serializer,
        private readonly HubInterface           $hub,
        private readonly MessageRepository      $messageRepository,
    )
    {
    }

    #[Route('/{id}/messages', methods: ['GET'])]
    public function getMessages(Chat $chat): JsonResponse
    {
        $this->denyAccessUnlessGranted('CHAT_ACCESS', $chat);

        $messages = $this->messageRepository->findByChatOrderedByDate($chat);

        return $this->json($messages, 200, [], ['groups' => ['message:read']]);
    }

    #[Route('/{id}/messages', methods: ['POST'])]
    public function sendMessage(Chat $chat, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('CHAT_ACCESS', $chat);

        $data = json_decode($request->getContent(), true);

        $message = (new Message())
            ->setContent($data['content'])
            ->setType($data['type'] ?? 'text')
            ->setChat($chat)
            ->setSender($this->getUser())
            ->setCreatedAt(new \DateTimeImmutable())
            ->setIsDeleted(false);

        if (isset($data['attachments'])) {
            $message->setAttachments($data['attachments']);
        }

        $this->em->persist($message);
        $this->em->flush();

        $this->publishMessage($message);

        return $this->json($message, 201, [], ['groups' => ['message:read']]);
    }

    #[Route('/list', methods: ['GET'])]
    public function getUserChats(ChatRepository $chatRepo): JsonResponse
    {
        $chats = $chatRepo->findByUser($this->getUser());

        return $this->json($chats, 200, [], ['groups' => ['chat:list']]);
    }

    #[Route('/{id}/mark-read', methods: ['POST'])]
    public function markAsRead(Chat $chat): JsonResponse
    {
        $this->denyAccessUnlessGranted('CHAT_ACCESS', $chat);

        $user = $this->getUser();
        $now = new \DateTimeImmutable();

        if ($chat->getUserOne() === $user) {
            $chat->setUserOneLastSeenAt($now);
        } elseif ($chat->getUserTwo() === $user) {
            $chat->setUserTwoLastSeenAt($now);
        }

        $this->em->flush();

        return $this->json(['status' => 'success']);
    }

    private function publishMessage(Message $message): void
    {
        $chat = $message->getChat();

        $update = new Update(
            sprintf('chat/%s', $chat->getId()),
            $this->serializer->serialize([
                'type' => 'message',
                'data' => $message
            ], 'json', ['groups' => ['message:read']])
        );

        $this->hub->publish($update);
    }
}
