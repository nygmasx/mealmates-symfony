<?php

namespace App\Command;

use App\Repository\ProductRepository;
use App\Service\AlertService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-expiring-products',
    description: 'Vérifie les produits qui approchent de leur expiration'
)]
class CheckExpiringProductsCommand extends Command
{
    public function __construct(
        private ProductRepository $productRepository,
        private AlertService $alertService,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $startDate = new \DateTime();
        $endDate = new \DateTime('+7 days'); // Vérifier 7 jours à l'avance

        $expiringProducts = $this->productRepository->findExpiringProducts($startDate, $endDate);

        $alertsSent = 0;

        foreach ($expiringProducts as $product) {
            $daysUntilExpiration = $this->calculateDaysUntilExpiration($product->getExpirationDate());

            if ($this->shouldSendAlert($product, $daysUntilExpiration)) {
                $this->alertService->sendExpirationAlert($product, $daysUntilExpiration);

                $product->setLastAlertSent(new \DateTime())
                    ->incrementAlertCount();

                $this->entityManager->persist($product);
                $alertsSent++;

                $io->info("Alerte envoyée pour le produit: {$product->getTitle()}");
            }
        }

        $this->entityManager->flush();

        $io->success("Vérification terminée. {$alertsSent} alertes envoyées sur {$expiringProducts->count()} produits vérifiés.");

        return Command::SUCCESS;
    }

    private function calculateDaysUntilExpiration(\DateTimeInterface $expirationDate): int
    {
        $now = new \DateTime();
        $interval = $now->diff($expirationDate);

        return $interval->days;
    }

    private function shouldSendAlert($product, int $daysUntilExpiration): bool
    {
        if ($daysUntilExpiration <= $product->getAlertDaysBefore() && $product->getAlertCount() === 0) {
            return true;
        }

        if ($daysUntilExpiration === 1) {
            $lastAlert = $product->getLastAlertSent();
            if (!$lastAlert || $lastAlert->diff(new \DateTime())->h >= 24) {
                return true;
            }
        }

        if ($daysUntilExpiration === 0) {
            $lastAlert = $product->getLastAlertSent();
            if (!$lastAlert || $lastAlert->diff(new \DateTime())->h >= 6) {
                return true;
            }
        }

        return false;
    }
}
