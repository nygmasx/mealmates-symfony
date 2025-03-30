<?php

namespace App\DataFixtures;

use App\Entity\Profile;
use App\Entity\Review;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ReviewFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $reviews = [
            [
                'author' => UserFixtures::REFERENCE_IDENTIFIER . 'emma',
                'recipient' => ProfileFixtures::PROFILE_JOHN,
            ],
            [
                'author' => UserFixtures::REFERENCE_IDENTIFIER . 'john',
                'recipient' => ProfileFixtures::PROFILE_EMMA,
            ],
            [
                'author' => UserFixtures::REFERENCE_IDENTIFIER . 'ADMIN',
                'recipient' => ProfileFixtures::PROFILE_USER,
            ],
            [
                'author' => UserFixtures::REFERENCE_IDENTIFIER . 'USER',
                'recipient' => ProfileFixtures::PROFILE_ADMIN,
            ],
        ];

        foreach ($reviews as $reviewData) {
            $review = new Review();
            $review->setAuthor($this->getReference($reviewData['author'], User::class));
            $review->setReviewedUserProfile($this->getReference($reviewData['recipient'], Profile::class));

            $manager->persist($review);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            ProfileFixtures::class,
        ];
    }
}
