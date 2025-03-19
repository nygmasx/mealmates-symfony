<?php

namespace App\DataFixtures;

use App\Entity\DietaryPreference;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DietaryPreferenceFixtures extends Fixture
{
    public const VEGETARIAN = 'vegetarian';
    public const VEGAN = 'vegan';
    public const PESCATARIAN = 'pescatarian';
    public const GLUTEN_FREE = 'gluten-free';
    public const LACTOSE_FREE = 'lactose-free';
    public const KETO = 'keto';
    public const PALEO = 'paleo';
    public const LOW_CARB = 'low-carb';
    public const HALAL = 'halal';

    public function load(ObjectManager $manager): void
    {
        $preferences = [
            [
                'reference' => self::VEGETARIAN,
                'name' => 'Végétarien',
            ],
            [
                'reference' => self::VEGAN,
                'name' => 'Vegan',
            ],
            [
                'reference' => self::PESCATARIAN,
                'name' => 'Pescatarien',
            ],
            [
                'reference' => self::GLUTEN_FREE,
                'name' => 'Sans Gluten',
            ],
            [
                'reference' => self::LACTOSE_FREE,
                'name' => 'Sans Lactose',
            ],
            [
                'reference' => self::KETO,
                'name' => 'Cétogène',
            ],
            [
                'reference' => self::PALEO,
                'name' => 'Paleo',
            ],
            [
                'reference' => self::LOW_CARB,
                'name' => 'Low Carb',
            ],
            [
                'reference' => self::HALAL,
                'name' => 'Halal',
            ],
        ];

        foreach ($preferences as $preferenceData) {
            $preference = new DietaryPreference();
            $preference->setName($preferenceData['name']);

            $manager->persist($preference);

            $this->addReference($preferenceData['reference'], $preference);
        }

        $manager->flush();
    }
}
