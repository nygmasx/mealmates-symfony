<?php

namespace App\Service;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class AlertService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface        $mailer,
        private Environment            $twig,
        private string                 $fromEmail = 'sallakimrane@9238764.brevosend.com',
        private string                 $siteUrl = 'https://mealmates.testingtest.fr/'
    )
    {
    }

    public function sendExpirationAlert(Product $product, int $daysUntilExpiration): void
    {
        $this->sendEmailAlert($product, $daysUntilExpiration);
    }

    private function sendEmailAlert(Product $product, int $daysUntilExpiration): void
    {
        $user = $product->getUser();

        if (!$user->getEmail()) {
            throw new \InvalidArgumentException("L'utilisateur n'a pas d'adresse email configurée");
        }

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($user->getEmail())
            ->subject($this->getEmailSubject($product, $daysUntilExpiration))
            ->html($this->twig->render('email/expiration_alert.html.twig', [
                'user' => $user,
                'product' => $product,
                'daysUntilExpiration' => $daysUntilExpiration,
                'urgencyLevel' => $this->getUrgencyLevel($daysUntilExpiration),
                'editUrl' => $this->generateEditUrl($product)
            ]));

        $this->mailer->send($email);
    }

    private function generateEditUrl(Product $product): string
    {
        return "{$this->siteUrl}/products/edit/{$product->getId()->toString()}";
    }

    private function getUrgencyLevel(int $daysUntilExpiration): string
    {
        return match (true) {
            $daysUntilExpiration === 0 => 'critical',
            $daysUntilExpiration === 1 => 'urgent',
            $daysUntilExpiration <= 3 => 'high',
            default => 'medium'
        };
    }

    private function getEmailSubject(Product $product, int $daysUntilExpiration): string
    {
        return match (true) {
            $daysUntilExpiration === 0 => "🚨 Action requise : {$product->getTitle()} expire aujourd'hui",
            $daysUntilExpiration === 1 => "⚠️ {$product->getTitle()} expire demain",
            default => "📅 {$product->getTitle()} expire dans {$daysUntilExpiration} jours"
        };
    }
}