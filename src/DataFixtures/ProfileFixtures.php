<?php

namespace App\DataFixtures;

use App\Entity\Availability;
use App\Entity\DietaryPreference;
use App\Entity\Profile;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProfileFixtures extends Fixture implements DependentFixtureInterface
{
    public const PROFILE_JOHN = 'profile-john';
    public const PROFILE_EMMA = 'profile-emma';
    public const PROFILE_ADMIN = 'profile-admin';
    public const PROFILE_USER = 'profile-user';

    public function load(ObjectManager $manager): void
    {
        $profiles = [
            [
                'reference' => self::PROFILE_JOHN,
                'user' => UserFixtures::REFERENCE_IDENTIFIER . 'john',
                'addressLine1' => '123 Main Street',
                'addressLine2' => 'Apt 4B',
                'city' => 'Paris',
                'zipCode' => '75001',
                'latitude' => 48.8566,
                'longitude' => 2.3522,
                'preferences' => [
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                    DietaryPreferenceFixtures::LOW_CARB,
                ],
                'availabilities' => [
                    AvailabilityFixtures::MONDAY,
                    AvailabilityFixtures::FRIDAY,
                ],
            ],
            [
                'reference' => self::PROFILE_EMMA,
                'user' => UserFixtures::REFERENCE_IDENTIFIER . 'emma',
                'addressLine1' => '456 Oak Avenue',
                'addressLine2' => null,
                'city' => 'Lyon',
                'zipCode' => '69001',
                'latitude' => 45.7640,
                'longitude' => 4.8357,
                'preferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                ],
                'availabilities' => [
                    AvailabilityFixtures::TUESDAY,
                    AvailabilityFixtures::WEDNESDAY,
                    AvailabilityFixtures::SUNDAY,
                ],
            ],
            [
                'reference' => self::PROFILE_ADMIN,
                'user' => UserFixtures::REFERENCE_IDENTIFIER . 'ADMIN',
                'addressLine1' => '789 Pine Road',
                'addressLine2' => 'Building C',
                'city' => 'Marseille',
                'zipCode' => '13001',
                'latitude' => 43.2965,
                'longitude' => 5.3698,
                'preferences' => [
                    DietaryPreferenceFixtures::VEGAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                ],
                'availabilities' => [
                    AvailabilityFixtures::THURSDAY,
                    AvailabilityFixtures::FRIDAY,
                    AvailabilityFixtures::SATURDAY,
                ],
            ],
            [
                'reference' => self::PROFILE_USER,
                'user' => UserFixtures::REFERENCE_IDENTIFIER . 'USER',
                'addressLine1' => '101 Maple Drive',
                'addressLine2' => null,
                'city' => 'Bordeaux',
                'zipCode' => '33000',
                'latitude' => 44.8378,
                'longitude' => -0.5792,
                'preferences' => [
                    DietaryPreferenceFixtures::PESCATARIAN,
                    DietaryPreferenceFixtures::KETO,
                ],
                'availabilities' => [
                    AvailabilityFixtures::MONDAY,
                    AvailabilityFixtures::WEDNESDAY,
                    AvailabilityFixtures::SUNDAY,
                ],
            ],
        ];

        foreach ($profiles as $profileData) {
            $profile = new Profile();
            $profile->setUser($this->getReference($profileData['user'], User::class));
            $profile->setAddressLine1($profileData['addressLine1']);
            $profile->setAddressLine2($profileData['addressLine2']);
            $profile->setCity($profileData['city']);
            $profile->setZipCode($profileData['zipCode']);
            $profile->setLatitude($profileData['latitude']);
            $profile->setLongitude($profileData['longitude']);

            foreach ($profileData['preferences'] as $preferenceRef) {
                $preference = $this->getReference($preferenceRef, DietaryPreference::class);
                $profile->addDietaryPreference($preference);
            }

            foreach ($profileData['availabilities'] as $availabilityRef) {
                $availability = $this->getReference($availabilityRef, Availability::class);
                $profile->addAvailability($availability);
            }

            $manager->persist($profile);

            $this->addReference($profileData['reference'], $profile);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            DietaryPreferenceFixtures::class,
            AvailabilityFixtures::class,
        ];
    }
}
