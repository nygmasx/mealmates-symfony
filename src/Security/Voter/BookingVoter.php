<?php

namespace App\Security\Voter;

use App\Entity\Booking;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class BookingVoter extends Voter
{
    const VIEW = 'booking_view';
    const RESPOND = 'booking_respond';
    const CANCEL = 'booking_cancel';
    const PAY = 'booking_pay';
    const GENERATE_QR = 'booking_generate_qr';
    const VALIDATE_TRANSACTION = 'booking_validate_transaction';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::VIEW,
            self::RESPOND,
            self::CANCEL,
            self::PAY,
            self::GENERATE_QR,
            self::VALIDATE_TRANSACTION
        ]) && $subject instanceof Booking;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Booking $booking */
        $booking = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($booking, $user),
            self::RESPOND => $this->canRespond($booking, $user),
            self::CANCEL => $this->canCancel($booking, $user),
            self::PAY => $this->canPay($booking, $user),
            self::GENERATE_QR => $this->canGenerateQR($booking, $user),
            self::VALIDATE_TRANSACTION => $this->canValidateTransaction($booking, $user),
            default => false,
        };
    }

    private function canView(Booking $booking, User $user): bool
    {
        return $this->isUserBuyer($booking, $user) || $this->isUserSeller($booking, $user);
    }

    private function canRespond(Booking $booking, User $user): bool
    {
        return $this->isUserSeller($booking, $user);
    }

    private function canCancel(Booking $booking, User $user): bool
    {
        return ($this->isUserBuyer($booking, $user) || $this->isUserSeller($booking, $user))
            && !$booking->isOutdated();
    }

    private function canPay(Booking $booking, User $user): bool
    {
        return $this->isUserBuyer($booking, $user)
            && $booking->isConfirmed()
            && !$booking->isPaid()
            && !$booking->isOutdated();
    }

    private function canGenerateQR(Booking $booking, User $user): bool
    {
        return $this->isUserBuyer($booking, $user)
            && $booking->isConfirmed()
            && ($booking->isPaid() || $booking->getTotalPrice() == 0)
            && !$booking->isDelivered();
    }

    private function canValidateTransaction(Booking $booking, User $user): bool
    {
        return $this->isUserSeller($booking, $user)
            && $booking->isConfirmed()
            && ($booking->isPaid() || $booking->getTotalPrice() == 0)
            && !$booking->isDelivered();
    }

    private function isUserBuyer(Booking $booking, User $user): bool
    {
        return $booking->getUser()?->getId() === $user->getId();
    }

    private function isUserSeller(Booking $booking, User $user): bool
    {
        return $booking->getProduct()->getUser() === $user;
    }
}
