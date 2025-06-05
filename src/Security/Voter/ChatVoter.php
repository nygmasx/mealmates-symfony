<?php

namespace App\Security\Voter;

use App\Entity\Chat;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ChatVoter extends Voter
{
    public const CHAT_ACCESS = 'CHAT_ACCESS';

    /**
     * @inheritDoc
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::CHAT_ACCESS && $subject instanceof Chat;
    }

    /**
     * @inheritDoc
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $chat = $subject;

        return $chat->getUserOne() === $user || $chat->getUserTwo() === $user;
    }
}
