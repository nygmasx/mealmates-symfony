<?php

namespace App\Service;

use App\Entity\Notification;
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
    )
    {
    }

    public function sendExpirationAlert(Product $product, int $daysUntilExpiration): void
    {
        $this->createInAppNotification($product, $daysUntilExpiration);

        $this->sendEmailAlert($product, $daysUntilExpiration);
    }

    private function createInAppNotification(Product $product, int $daysUntilExpiration): void
    {
        $urgencyLevel = $this->getUrgencyLevel($daysUntilExpiration);

        $notification = new Notification();
        $notification->setUser($product->getUser())
            ->setProduct($product)
            ->setType('expiration_alert')
            ->setTitle($this->getAlertTitle($daysUntilExpiration))
            ->setMessage($this->getAlertMessage($product, $daysUntilExpiration))
            ->setData([
                'product_id' => $product->getId(),
                'days_until_expiration' => $daysUntilExpiration,
                'urgency_level' => $urgencyLevel,
                'actions' => $this->getAvailableActions($product),
                'edit_url' => "/products/edit/{$product->getId()}"
            ]);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }

    private function sendEmailAlert(Product $product, int $daysUntilExpiration): void
    {
        $user = $product->getUser();

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($user->getEmail())
            ->subject($this->getEmailSubject($product, $daysUntilExpiration))
            ->html($this->twig->render('emails/expiration_alert.html.twig', [
                'user' => $user,
                'product' => $product,
                'daysUntilExpiration' => $daysUntilExpiration,
                'urgencyLevel' => $this->getUrgencyLevel($daysUntilExpiration),
                'actions' => $this->getAvailableActions($product),
                'editUrl' => "https://yoursite.com/products/edit/{$product->getId()}"
            ]));

        $this->mailer->send($email);
    }

    private function getUrgencyLevel(int $daysUntilExpiration): string
    {
        if ($daysUntilExpiration === 0) return 'critical';
        if ($daysUntilExpiration === 1) return 'urgent';
        if ($daysUntilExpiration <= 3) return 'high';
        return 'medium';
    }

    private function getAlertTitle(int $daysUntilExpiration): string
    {
        if ($daysUntilExpiration === 0) {
            return '🚨 Produit expire aujourd\'hui !';
        } elseif ($daysUntilExpiration === 1) {
            return '⚠️ Produit expire demain';
        } else {
            return "📅 Produit expire dans {$daysUntilExpiration} jours";
        }
    }

    private function getAlertMessage(Product $product, int $daysUntilExpiration): string
    {
        $productName = $product->getTitle();

        if ($daysUntilExpiration === 0) {
            return "Votre produit \"{$productName}\" expire aujourd'hui. Considérez le passer en don gratuit pour éviter le gaspillage.";
        } elseif ($daysUntilExpiration === 1) {
            return "Votre produit \"{$productName}\" expire demain. Vous pouvez réduire le prix ou le passer en don.";
        } else {
            return "Votre produit \"{$productName}\" expire dans {$daysUntilExpiration} jours. Pensez à ajuster le prix pour accélérer la vente.";
        }
    }

    private function getEmailSubject(Product $product, int $daysUntilExpiration): string
    {
        if ($daysUntilExpiration === 0) {
            return "🚨 Action requise : {$product->getTitle()} expire aujourd'hui";
        } elseif ($daysUntilExpiration === 1) {
            return "⚠️ {$product->getTitle()} expire demain";
        } else {
            return "📅 {$product->getTitle()} expire dans {$daysUntilExpiration} jours";
        }
    }

    private function getAvailableActions(Product $product): array
    {
        $actions = [
            'edit' => 'Modifier le produit',
            'reduce_price' => 'Réduire le prix',
        ];

        if ($product->getPrice() > 0) {
            $actions['convert_to_donation'] = 'Convertir en don gratuit';
        }

        return $actions;
    }
}
