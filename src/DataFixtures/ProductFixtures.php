<?php

namespace App\DataFixtures;

use App\Entity\DietaryPreference;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use DateTimeImmutable;

class ProductFixtures extends Fixture implements DependentFixtureInterface
{
    public const FRUITS = 'fruits';
    public const VEGETABLES = 'vegetables';
    public const PREPARED_MEAL = 'prepared_meal';
    public const CAKE = 'cake';
    public const DAIRY_PRODUCT = 'dairy_product';

    public function load(ObjectManager $manager): void
    {
        $admin = $this->getReference(UserFixtures::REFERENCE_IDENTIFIER . 'ADMIN', User::class);
        $user = $this->getReference(UserFixtures::REFERENCE_IDENTIFIER . 'USER', User::class);
        $john = $this->getReference(UserFixtures::REFERENCE_IDENTIFIER . 'john', User::class);
        $emma = $this->getReference(UserFixtures::REFERENCE_IDENTIFIER . 'emma', User::class);

        $products = [
            [
                'title' => 'Pommes Fraîches',
                'expiresAt' => new DateTimeImmutable('+10 days'),
                'price' => 2.99,
                'user' => $john,
                'type' => self::FRUITS,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::VEGAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                    DietaryPreferenceFixtures::LACTOSE_FREE,
                    DietaryPreferenceFixtures::PALEO,
                ],
            ],
            [
                'title' => 'Bananes Bio',
                'expiresAt' => new DateTimeImmutable('+7 days'),
                'user' => $user,
                'price' => 1.99,
                'type' => self::FRUITS,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::VEGAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                    DietaryPreferenceFixtures::LACTOSE_FREE,
                ],
            ],
            [
                'title' => 'Carottes Fraîches',
                'expiresAt' => new DateTimeImmutable('+14 days'),
                'user' => $admin,
                'price' => 1.49,
                'type' => self::VEGETABLES,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::VEGAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                    DietaryPreferenceFixtures::LACTOSE_FREE,
                    DietaryPreferenceFixtures::KETO,
                    DietaryPreferenceFixtures::PALEO,
                    DietaryPreferenceFixtures::LOW_CARB,
                ],
            ],
            [
                'title' => 'Brocolis Bio',
                'expiresAt' => new DateTimeImmutable('+10 days'),
                'user' => $user,
                'price' => 2.29,
                'type' => self::VEGETABLES,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::VEGAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                    DietaryPreferenceFixtures::LACTOSE_FREE,
                    DietaryPreferenceFixtures::KETO,
                    DietaryPreferenceFixtures::PALEO,
                    DietaryPreferenceFixtures::LOW_CARB,
                ],
            ],
            [
                'title' => 'Curry de Légumes',
                'expiresAt' => new DateTimeImmutable('+3 days'),
                'user' => $emma,
                'price' => 8.99,
                'type' => self::PREPARED_MEAL,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::VEGAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                    DietaryPreferenceFixtures::LACTOSE_FREE,
                    DietaryPreferenceFixtures::HALAL,
                ],
            ],
            [
                'title' => 'Gâteau au Chocolat',
                'expiresAt' => new DateTimeImmutable('+5 days'),
                'user' => $user,
                'price' => 12.99,
                'type' => self::CAKE,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                ],
            ],
            [
                'title' => 'Yaourt Grec',
                'expiresAt' => new DateTimeImmutable('+14 days'),
                'user' => $john,
                'price' => 3.49,
                'type' => self::DAIRY_PRODUCT,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                    DietaryPreferenceFixtures::KETO,
                    DietaryPreferenceFixtures::LOW_CARB,
                ],
            ],
            [
                'title' => 'Fromage Cheddar Bio',
                'expiresAt' => new DateTimeImmutable('+30 days'),
                'user' => $admin,
                'price' => 5.99,
                'type' => self::DAIRY_PRODUCT,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                    DietaryPreferenceFixtures::KETO,
                    DietaryPreferenceFixtures::LOW_CARB,
                ],
            ],
            [
                'title' => 'Saumon Grillé aux Légumes',
                'expiresAt' => new DateTimeImmutable('+2 days'),
                'user' => $emma,
                'price' => 14.99,
                'type' => self::PREPARED_MEAL,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::PESCATARIAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                    DietaryPreferenceFixtures::LACTOSE_FREE,
                    DietaryPreferenceFixtures::KETO,
                    DietaryPreferenceFixtures::PALEO,
                    DietaryPreferenceFixtures::LOW_CARB,
                ],
            ],
            [
                'title' => 'Burger Végétal',
                'expiresAt' => new DateTimeImmutable('+7 days'),
                'user' => $john,
                'price' => 9.99,
                'type' => self::PREPARED_MEAL,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::VEGAN,
                    DietaryPreferenceFixtures::HALAL,
                ],
            ],
        ];

        foreach ($products as $productData) {
            $product = new Product();
            $product->setTitle($productData['title'])
                ->setExpiresAt($productData['expiresAt'])
                ->setPrice($productData['price'])
                ->setType($productData['type'])
                ->setUser($productData['user']);

            foreach ($productData['dietaryPreferences'] as $preferenceReference) {
                $preference = $this->getReference($preferenceReference, DietaryPreference::class);
                $product->addDietaryPreference($preference);
            }

            $manager->persist($product);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            DietaryPreferenceFixtures::class,
        ];
    }
}
