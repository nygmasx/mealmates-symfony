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
    public const PROFILE_USER2 = 'profile-user2';
    public const PROFILE_USER3 = 'profile-user3';
    public const PROFILE_USER4 = 'profile-user4';
    public const PROFILE_USER5 = 'profile-user5';
    public const PROFILE_USER6 = 'profile-user6';

    public function load(ObjectManager $manager): void
    {
        $profiles = [
            [
                'reference' => self::PROFILE_JOHN,
                'user' => UserFixtures::REFERENCE_IDENTIFIER . 'john',
                'addressLine1' => '15 Rue de la République',
                'addressLine2' => 'Apt 2C',
                'city' => 'Senlis',
                'zipCode' => '60300',
                'latitude' => 49.201493,
                'longitude' => 2.581847,
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
                'addressLine1' => '8 Avenue de Chantilly',
                'addressLine2' => null,
                'city' => 'Senlis',
                'zipCode' => '60300',
                'latitude' => 49.206474,
                'longitude' => 2.576708,
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
                'addressLine1' => '23 Rue du Châtel',
                'addressLine2' => 'Résidence Les Tilleuls',
                'city' => 'Senlis',
                'zipCode' => '60300',
                'latitude' => 49.206060,
                'longitude' => 2.584180,
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
                'addressLine1' => '4 Place Henri IV',
                'addressLine2' => null,
                'city' => 'Senlis',
                'zipCode' => '60300',
                'latitude' => 49.205300,
                'longitude' => 2.583540,
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
            [
                'reference' => self::PROFILE_USER2,
                'user' => UserFixtures::REFERENCE_IDENTIFIER . 'USER2',
                'addressLine1' => '45 Avenue du Général de Gaulle',
                'addressLine2' => null,
                'city' => 'Chantilly',
                'zipCode' => '60500',
                'latitude' => 49.2819852,
                'longitude' => 2.5191566,
                'preferences' => [
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                    DietaryPreferenceFixtures::VEGAN,
                ],
                'availabilities' => [
                    AvailabilityFixtures::WEDNESDAY,
                    AvailabilityFixtures::FRIDAY,
                    AvailabilityFixtures::SUNDAY,
                ],
            ],
            [
                'reference' => self::PROFILE_USER3,
                'user' => UserFixtures::REFERENCE_IDENTIFIER . 'USER3',
                'addressLine1' => '7 Rue Saint-Pierre',
                'addressLine2' => 'Bâtiment A',
                'city' => 'Chamant',
                'zipCode' => '60300',
                'latitude' => 49.206974029541016,
                'longitude' => 2.58734393119812,
                'preferences' => [
                    DietaryPreferenceFixtures::LOW_CARB,
                    DietaryPreferenceFixtures::PESCATARIAN,
                ],
                'availabilities' => [
                    AvailabilityFixtures::MONDAY,
                    AvailabilityFixtures::THURSDAY,
                    AvailabilityFixtures::SATURDAY,
                ],
            ],
            [
                'reference' => self::PROFILE_USER4,
                'user' => UserFixtures::REFERENCE_IDENTIFIER . 'USER4',
                'addressLine1' => 'Place Jean Davidsen',
                'addressLine2' => null,
                'city' => 'Senlis',
                'zipCode' => '60300',
                'latitude' => 49.20655822753906,
                'longitude' => 2.570784091949463,
                'preferences' => [
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                    DietaryPreferenceFixtures::VEGAN,
                ],
                'availabilities' => [
                    AvailabilityFixtures::TUESDAY,
                    AvailabilityFixtures::FRIDAY,
                    AvailabilityFixtures::SUNDAY,
                ],
            ],
            [
                'reference' => self::PROFILE_USER5,
                'user' => UserFixtures::REFERENCE_IDENTIFIER . 'USER5',
                'addressLine1' => '27 Avenue de Beauval',
                'addressLine2' => 'Résidence Les Érables',
                'city' => 'Senlis',
                'zipCode' => '60300',
                'latitude' => 49.2132,
                'longitude' => 2.5847,
                'preferences' => [
                    DietaryPreferenceFixtures::HALAL,
                    DietaryPreferenceFixtures::KETO,
                ],
                'availabilities' => [
                    AvailabilityFixtures::MONDAY,
                    AvailabilityFixtures::WEDNESDAY,
                    AvailabilityFixtures::FRIDAY,
                ],
            ],
            [
                'reference' => self::PROFILE_USER6,
                'user' => UserFixtures::REFERENCE_IDENTIFIER . 'USER6',
                'addressLine1' => '9 Rue des Jardins',
                'addressLine2' => 'Apt 3B',
                'city' => 'Senlis',
                'zipCode' => '60300',
                'latitude' => 49.2098,
                'longitude' => 2.5918,
                'preferences' => [
                    DietaryPreferenceFixtures::PALEO,
                    DietaryPreferenceFixtures::PESCATARIAN,
                ],
                'availabilities' => [
                    AvailabilityFixtures::TUESDAY,
                    AvailabilityFixtures::THURSDAY,
                    AvailabilityFixtures::SATURDAY,
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
