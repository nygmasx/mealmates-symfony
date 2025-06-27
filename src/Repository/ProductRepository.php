<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\Profile;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DateTimeImmutable;

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

    public function findProductsByPreviousSellers(array $sellerIds): array
    {
        if (empty($sellerIds)) {
            return [];
        }

        return $this->createQueryBuilder('p')
            ->select('p, u')
            ->join('p.user', 'u')
            ->where('u.id IN (:sellerIds)')
            ->setParameter('sellerIds', $sellerIds)
            ->orderBy('p.expiresAt', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function findExpiringToday(): array
    {
        $startOfDay = new \DateTimeImmutable('today midnight', new \DateTimeZone('Europe/Paris'));
        $endOfDay = new \DateTimeImmutable('today 23:59:59', new \DateTimeZone('Europe/Paris'));

        return $this->createQueryBuilder('p')
            ->select('p, u')
            ->join('p.user', 'u')
            ->where('p.expiresAt BETWEEN :start AND :end')
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay)
            ->orderBy('p.price', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function findVeganProducts(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p, u')
            ->join('p.user', 'u')
            ->join('p.dietaryPreferences', 'dp')
            ->where('dp.name = :preference')
            ->setParameter('preference', 'vegan')
            ->orderBy('p.price', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function findLocalTrendingProducts(float $latitude, float $longitude, float $radius = 10.0): array
    {
        $latDiff = $radius / 111.0;
        $longDiff = $radius / (111.0 * cos(deg2rad($latitude)));
        $minLat = $latitude - $latDiff;
        $maxLat = $latitude + $latDiff;
        $minLong = $longitude - $longDiff;
        $maxLong = $longitude + $longDiff;

        return $this->createQueryBuilder('p')
            ->select('p, u')
            ->join('p.user', 'u')
            ->join('u.profile', 'pr')
            ->where('pr.latitude BETWEEN :minLat AND :maxLat')
            ->andWhere('pr.longitude BETWEEN :minLong AND :maxLong')
            ->setParameter('minLat', $minLat)
            ->setParameter('maxLat', $maxLat)
            ->setParameter('minLong', $minLong)
            ->setParameter('maxLong', $maxLong)
            ->orderBy('p.expiresAt', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function findTrendingProducts(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p, u')
            ->join('p.user', 'u')
            ->where('p.expiresAt > :tomorrow')
            ->setParameter('tomorrow', new DateTimeImmutable('tomorrow'))
            ->orderBy('p.expiresAt', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function findCustomizedProducts(Profile $profile): array
    {
        $dietaryPreferences = $profile->getDietaryPreferences();

        if (!$dietaryPreferences || count($dietaryPreferences) === 0) {
            return $this->findProductsByFilters([
                'expiresAt' => (new \DateTimeImmutable())->modify('+3 days')->format('Y-m-d')
            ]);
        }

        $preferenceIds = array_map(
            fn($preference) => $preference->getId(),
            $dietaryPreferences->toArray()
        );

        return $this->createQueryBuilder('p')
            ->select('p', 'u')
            ->join('p.user', 'u')
            ->join('p.dietaryPreferences', 'dp')
            ->where('dp.id IN (:preferences)')
            ->setParameter('preferences', $preferenceIds)
            ->orderBy('p.expiresAt', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function findExpiringProducts(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.expirationDate > :startDate')
            ->andWhere('p.expirationDate <= :endDate')
            ->andWhere('p.alertEnabled = :alertEnabled')
            ->andWhere('p.isActive = :isActive')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('alertEnabled', true)
            ->setParameter('isActive', true)
            ->innerJoin('p.user', 'u')
            ->addSelect('u')
            ->getQuery()
            ->getResult();
    }

    public function findUserExpiringProducts($user, int $days = 7): array
    {
        $endDate = new \DateTime("+{$days} days");

        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.expirationDate <= :endDate')
            ->andWhere('p.expirationDate > :now')
            ->andWhere('p.isActive = :isActive')
            ->setParameter('user', $user)
            ->setParameter('endDate', $endDate)
            ->setParameter('now', new \DateTime())
            ->setParameter('isActive', true)
            ->orderBy('p.expirationDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

}
