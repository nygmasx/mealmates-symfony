<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class BookingService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductRepository $productRepository,
        private readonly NotificationService $notificationService
    ) {}

    public function createBooking(User $buyer, array $productIds): Booking
    {
        $products = $this->productRepository->findBy(['id' => $productIds]);

        if (count($products) !== count($productIds)) {
            throw new \InvalidArgumentException('Un ou plusieurs produits sont introuvables');
        }

        $seller = null;
        $totalPrice = 0;

        foreach ($products as $product) {
            if (!$product->isActive()) {
                throw new \InvalidArgumentException("Le produit '{$product->getTitle()}' n'est plus disponible");
            }

            if ($product->getUser() === $buyer) {
                throw new \InvalidArgumentException('Vous ne pouvez pas réserver vos propres produits');
            }

            if ($seller === null) {
                $seller = $product->getUser();
            } elseif ($seller !== $product->getUser()) {
                throw new \InvalidArgumentException('Tous les produits doivent appartenir au même vendeur');
            }

            if ($this->hasActiveBooking($product, $buyer)) {
                throw new \InvalidArgumentException("Vous avez déjà une réservation active pour '{$product->getTitle()}'");
            }

            $totalPrice += $product->getPrice();
        }

        $booking = new Booking();
        $booking->setUser($buyer)
        ->setCreatedAt(new \DateTimeImmutable())
            ->setIsConfirmed(false)
            ->setIsOutdated(false)
            ->setTotalPrice($totalPrice);

        foreach ($products as $product) {
            $booking->addProduct($product);
        }

        $this->entityManager->persist($booking);
        $this->entityManager->flush();

        $this->notificationService->sendBookingOpenedMail($booking);
        $this->notificationService->sendBookingOpenedToSellerMail($booking);

        return $booking;
    }

    public function confirmBooking(Booking $booking): void
    {
        if ($booking->isConfirmed() || $booking->isOutdated()) {
            throw new \InvalidArgumentException('Cette réservation a déjà été traitée');
        }

        $booking->setIsConfirmed(true)
            ->setConfirmedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->notificationService->sendBookingConfirmationNotification($booking);
    }

    public function rejectBooking(Booking $booking): void
    {
        if ($booking->isConfirmed() || $booking->isOutdated()) {
            throw new \InvalidArgumentException('Cette réservation a déjà été traitée');
        }

        $booking->setIsOutdated(true)
            ->setOutdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->notificationService->sendBookingRejectionNotification($booking);
    }

    public function expireBooking(Booking $booking): void
    {
        if ($booking->isConfirmed() || $booking->isOutdated()) {
            return;
        }

        $booking->setIsOutdated(true)
            ->setOutdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->notificationService->sendBookingExpirationNotification($booking);
    }

    private function hasActiveBooking(Product $product, User $buyer): bool
    {
        foreach ($product->getBookings() as $booking) {
            if ($booking->getUser() === $buyer &&
                !$booking->isConfirmed() &&
                !$booking->isOutdated()) {
                return true;
            }
        }
        return false;
    }
}
