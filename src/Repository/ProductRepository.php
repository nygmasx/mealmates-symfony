<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findProducsWithtLocations(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p, u, pr, pr.longitude, pr.latitude')
            ->join('p.user', 'u')
            ->join('u.profile', 'pr')
            ->getQuery()
            ->getResult();
    }

    public function findProductsByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p, u')
            ->join('p.user', 'u');

        error_log('Filtres reçus: ' . json_encode($filters));

        if (isset($filters['type']) && $filters['type']) {
            $qb->andWhere('p.type = :type')
                ->setParameter('type', $filters['type']);
        }

        if (isset($filters['expiresAt']) && $filters['expiresAt']) {
            try {
                $expiresAt = new \DateTimeImmutable($filters['expiresAt']);
                $qb->andWhere('p.expiresAt <= :expiresAt')
                    ->setParameter('expiresAt', $expiresAt);
            } catch (\Exception $e) {
                error_log('Erreur de format de date: ' . $e->getMessage());
            }
        }

        if (isset($filters['dietaryPreferences']) && !empty($filters['dietaryPreferences'])) {

            if (!is_array($filters['dietaryPreferences'])) {
                $filters['dietaryPreferences'] = [$filters['dietaryPreferences']];
            }

            foreach ($filters['dietaryPreferences'] as $index => $prefId) {
                $paramName = 'dietPref' . $index;
                $qb->andWhere(
                    $qb->expr()->exists(
                        'SELECT dp' . $index . ' FROM App\Entity\DietaryPreference dp' . $index .
                        ' JOIN p.dietaryPreferences pdp' . $index .
                        ' WHERE pdp' . $index . '.id = dp' . $index . '.id AND dp' . $index . '.id = :' . $paramName
                    )
                )
                    ->setParameter($paramName, $prefId);
            }
        }

        if (isset($filters['maxPrice']) && $filters['maxPrice'] !== null) {
            $maxPrice = is_numeric($filters['maxPrice']) ? (float)$filters['maxPrice'] : null;
            if ($maxPrice !== null) {
                $qb->andWhere('p.price <= :maxPrice')
                    ->setParameter('maxPrice', $maxPrice);
            }
        }

        if (isset($filters['minRating']) && $filters['minRating'] !== null) {
            $minRating = is_numeric($filters['minRating']) ? (float)$filters['minRating'] : null;
            if ($minRating !== null) {
                $qb->andWhere('u.rating >= :minRating')
                    ->setParameter('minRating', $minRating);
            }
        }

        $query = $qb->getQuery();

        return $query->getResult();
    }
}
