<?php

namespace App\Repository;

use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * @return Review[] Returns visible reviews for a specific user
     */
    public function findVisibleReviewsForUser($user): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.reviewedUser = :user')
            ->andWhere('r.isVisible = true')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find if a review already exists for a specific booking and review type
     */
    public function findByBookingAndType($booking, string $reviewType): ?Review
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.booking = :booking')
            ->andWhere('r.reviewType = :reviewType')
            ->setParameter('booking', $booking)
            ->setParameter('reviewType', $reviewType)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Calculate average ratings for a user
     */
    public function getAverageRatingsForUser($user): array
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.overallRating) as avgOverall')
            ->addSelect('COUNT(r.id) as totalReviews')
            ->addSelect('AVG(r.productQualityRating) as avgProductQuality')
            ->addSelect('AVG(r.punctualityRating) as avgPunctuality')
            ->addSelect('AVG(r.friendlinessRating) as avgFriendliness')
            ->addSelect('AVG(r.communicationRating) as avgCommunication')
            ->addSelect('AVG(r.reliabilityRating) as avgReliability')
            ->andWhere('r.reviewedUser = :user')
            ->andWhere('r.isVisible = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();

        return [
            'averageOverallRating' => $result['avgOverall'] ? round((float)$result['avgOverall'], 1) : null,
            'totalReviews' => (int)$result['totalReviews'],
            'averageProductQuality' => $result['avgProductQuality'] ? round((float)$result['avgProductQuality'], 1) : null,
            'averagePunctuality' => $result['avgPunctuality'] ? round((float)$result['avgPunctuality'], 1) : null,
            'averageFriendliness' => $result['avgFriendliness'] ? round((float)$result['avgFriendliness'], 1) : null,
            'averageCommunication' => $result['avgCommunication'] ? round((float)$result['avgCommunication'], 1) : null,
            'averageReliability' => $result['avgReliability'] ? round((float)$result['avgReliability'], 1) : null,
        ];
    }

    /**
     * Get reviews that need moderation
     */
    public function findReviewsNeedingModeration(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.moderatedAt IS NULL')
            ->andWhere('r.isVisible = true')
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
