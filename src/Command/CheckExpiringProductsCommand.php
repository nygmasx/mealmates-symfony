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
    description: 'Envoie des alertes 48h avant expiration'
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

        $productsExpiring = $this->productRepository->findProductsExpiringInDays(2);

        $alertsSent = 0;

        foreach ($productsExpiring as $product) {
            if ($this->hasRecentAlert($product)) {
                continue;
            }

            $this->alertService->sendExpirationAlert($product, 2);

            $product->setLastAlertSentAt(new \DateTimeImmutable());
            $product->setAlertCount(($product->getAlertCount() ?? 0) + 1);
            $this->entityManager->persist($product);

            $alertsSent++;
            $io->info("Alerte envoyée pour: {$product->getTitle()}");
        }

        $this->entityManager->flush();

        $io->success("{$alertsSent} alertes envoyées.");
        return Command::SUCCESS;
    }

    private function hasRecentAlert($product): bool
    {
        $lastAlert = $product->getLastAlertSentAt();

        if (!$lastAlert) {
            return false; // Jamais d'alerte envoyée
        }

        $hoursSince = $lastAlert->diff(new \DateTimeImmutable())->h;
        return $hoursSince < 24;
    }
}