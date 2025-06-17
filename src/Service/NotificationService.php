<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationService
{
    public function __construct(private readonly MailerInterface $mailer, private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function sendVerificationEmail(User $user): void
    {
        $verificationUrl = $this->urlGenerator->generate(
            'app_user_verify',
            ['token' => $user->getVerificationToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = new TemplatedEmail()
            ->from(new Address('sallakimrane@gmail.com', 'Meal Mates'))
            ->to($user->getEmail())
            ->subject('Veuillez confirmer votre adresse email')
            ->htmlTemplate('email/registration.html.twig')
            ->context([
                'verificationUrl' => $verificationUrl,
                'user' => $user,
            ]);

        $email->getHeaders()
            ->addTextHeader('X-Mailin-Tag', 'account-verification');

        $this->mailer->send($email);
    }

    public function sendBookingOpenedMail(Booking $booking): void
    {
        $email = new TemplatedEmail()
            ->from(new Address('sallakimrane@gmail.com', 'Meal Mates'))
            ->to($booking->getUser()->getEmail())
            ->subject("Votre réservation a été créée avec succès.")
            ->htmlTemplate('email/booking/opened.html.twig')
            ->context([
                'booking' => $booking,
            ]);

        $email->getHeaders()
            ->addTextHeader('X-Mailin-Tag', 'booking-opened');

        $this->mailer->send($email);
    }

    public function sendBookingOpenedToSellerMail(Booking $booking): void
    {
        $userName = $booking->getUser()->getFirstName() . ' ' . $booking->getUser()->getLastName();
        $sellerName = $booking->getProducts()->first()->getUser()->getFirstName() . ' ' . $booking->getProducts()->first()->getUser()->getLastName();

        $email = new TemplatedEmail()
            ->from(new Address('sallakimrane@gmail.com', 'Meal Mates'))
            ->to($booking->getProducts()->first()->getUser()->getEmail())
            ->subject("Nouvelle réservation de {$userName}.")
            ->htmlTemplate('email/booking/opened_to_seller.html.twig')
            ->context([
                'booking' => $booking,
                'sellerName' => $sellerName,
            ]);

        $email->getHeaders()
            ->addTextHeader('X-Mailin-Tag', 'booking-opened-to-seller');

        $this->mailer->send($email);
    }

    public function sendBookingConfirmationNotification(Booking $booking): void
    {
        $seller = $booking->getProducts()->first()->getUser();
        $sellerName = $seller->getFirstName() . ' ' . $seller->getLastName();
        $buyerName = $booking->getUser()->getFirstName() . ' ' . $booking->getUser()->getLastName();

        $email = new TemplatedEmail()
            ->from(new Address('sallakimrane@gmail.com', 'Meal Mates'))
            ->to($booking->getUser()->getEmail())
            ->subject("✅ {$buyerName}, votre réservation a été confirmée !")
            ->htmlTemplate('email/booking/confirmed.html.twig')
            ->context([
                'booking' => $booking,
                'buyerName' => $buyerName,
                'sellerName' => $sellerName,
            ]);

        $email->getHeaders()
            ->addTextHeader('X-Mailin-Tag', 'booking-confirmed');

        $this->mailer->send($email);
    }

    public function sendBookingRejectionNotification(Booking $booking): void
    {
        $seller = $booking->getProducts()->first()->getUser();
        $sellerName = $seller->getFirstName() . ' ' . $seller->getLastName();
        $buyerName = $booking->getUser()->getFirstName() . ' ' . $booking->getUser()->getLastName();

        $email = new TemplatedEmail()
            ->from(new Address('sallakimrane@gmail.com', 'Meal Mates'))
            ->to($booking->getUser()->getEmail())
            ->subject("❌ {$buyerName}, votre demande de réservation a été refusée")
            ->htmlTemplate('email/booking/rejected.html.twig')
            ->context([
                'booking' => $booking,
                'buyerName' => $buyerName,
                'sellerName' => $sellerName,
            ]);

        $email->getHeaders()
            ->addTextHeader('X-Mailin-Tag', 'booking-rejected');

        $this->mailer->send($email);
    }

    public function sendBookingExpirationNotification(Booking $booking): void
    {
        $seller = $booking->getProducts()->first()->getUser();
        $sellerName = $seller->getFirstName() . ' ' . $seller->getLastName();
        $buyerName = $booking->getUser()->getFirstName() . ' ' . $booking->getUser()->getLastName();

        $email = new TemplatedEmail()
            ->from(new Address('sallakimrane@gmail.com', 'Meal Mates'))
            ->to($booking->getUser()->getEmail())
            ->subject("⏰ {$buyerName}, votre réservation a expiré")
            ->htmlTemplate('email/booking/expired.html.twig')
            ->context([
                'booking' => $booking,
                'buyerName' => $buyerName,
                'sellerName' => $sellerName,
            ]);

        $email->getHeaders()
            ->addTextHeader('X-Mailin-Tag', 'booking-expired');

        $this->mailer->send($email);

    }

    public function sendBookingCancellationNotification(Booking $booking): void
    {
        $seller = $booking->getProducts()->first()->getUser();
        $buyer = $booking->getUser();

        $email = new TemplatedEmail()
            ->from(new Address('sallakimrane@gmail.com', 'Meal Mates'))
            ->to($seller->getEmail())
            ->subject("🚫 Réservation annulée")
            ->htmlTemplate('email/booking/cancelled.html.twig')
            ->context([
                'booking' => $booking,
                'recipientName' => $seller->getFirstName() . ' ' . $seller->getLastName(),
                'otherPartyName' => $buyer->getFirstName() . ' ' . $buyer->getLastName(),
                'recipientRole' => 'seller'
            ]);

        $email->getHeaders()
            ->addTextHeader('X-Mailin-Tag', 'booking-cancelled');

        $this->mailer->send($email);

        $email = new TemplatedEmail()
            ->from(new Address('sallakimrane@gmail.com', 'Meal Mates'))
            ->to($buyer->getEmail())
            ->subject("🚫 Réservation annulée")
            ->htmlTemplate('email/booking/cancelled.html.twig')
            ->context([
                'booking' => $booking,
                'recipientName' => $buyer->getFirstName() . ' ' . $buyer->getLastName(),
                'otherPartyName' => $seller->getFirstName() . ' ' . $seller->getLastName(),
                'recipientRole' => 'buyer'
            ]);

        $this->mailer->send($email);
    }
}
