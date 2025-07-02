<?php

namespace App\Security\Voter;

use App\Entity\Review;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ReviewVoter extends Voter
{
    public const CREATE = 'CREATE_REVIEW';
    public const VIEW = 'VIEW_REVIEW';
    public const EDIT = 'EDIT_REVIEW';
    public const DELETE = 'DELETE_REVIEW';
    public const MODERATE = 'MODERATE_REVIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::CREATE, self::VIEW, self::EDIT, self::DELETE, self::MODERATE])) {
            return false;
        }

        if ($attribute === self::CREATE) {
            return true;
        }

        return $subject instanceof Review;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::CREATE => $this->canCreate($user),
            self::VIEW => $this->canView($subject, $user),
            self::EDIT => $this->canEdit($subject, $user),
            self::DELETE => $this->canDelete($subject, $user),
            self::MODERATE => $this->canModerate($user),
            default => false,
        };
    }

    private function canCreate(User $user): bool
    {
        return true;
    }

    private function canView(Review $review, User $user): bool
    {
        if (!$review->isVisible() && !$this->canModerate($user)) {
            return false;
        }

        if ($review->getAuthor() === $user || $review->getReviewedUser() === $user) {
            return true;
        }

        if ($this->canModerate($user)) {
            return true;
        }

        return $review->isVisible();
    }

    private function canEdit(Review $review, User $user): bool
    {
        if ($review->getAuthor() === $user) {
            $timeSinceCreation = (new \DateTimeImmutable())->diff($review->getCreatedAt());
            return $timeSinceCreation->days < 7;
        }

        return $this->canModerate($user);
    }

    private function canDelete(Review $review, User $user): bool
    {
        if ($review->getAuthor() === $user) {
            $timeSinceCreation = (new \DateTimeImmutable())->diff($review->getCreatedAt());
            return $timeSinceCreation->days < 1;
        }

        return $this->canModerate($user);
    }

    private function canModerate(User $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_MODERATOR', $user->getRoles());
    }
}