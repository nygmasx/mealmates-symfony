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
        $user2 = $this->getReference(UserFixtures::REFERENCE_IDENTIFIER . 'USER2', User::class);
        $user3 = $this->getReference(UserFixtures::REFERENCE_IDENTIFIER . 'USER3', User::class);
        $user4 = $this->getReference(UserFixtures::REFERENCE_IDENTIFIER . 'USER4', User::class);
        $user5 = $this->getReference(UserFixtures::REFERENCE_IDENTIFIER . 'USER5', User::class);
        $user6 = $this->getReference(UserFixtures::REFERENCE_IDENTIFIER . 'USER6', User::class);

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
            [
                'title' => 'Fraises Bio de Senlis',
                'expiresAt' => new DateTimeImmutable('+5 days'),
                'user' => $user,
                'price' => 4.99,
                'type' => self::FRUITS,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::VEGAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                    DietaryPreferenceFixtures::PALEO,
                ],
            ],
            [
                'title' => 'Pain aux Céréales Maison',
                'expiresAt' => new DateTimeImmutable('+3 days'),
                'user' => $user,
                'price' => 3.50,
                'type' => self::FRUITS,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::VEGAN,
                ],
            ],
            [
                'title' => 'Salade de Quinoa aux Légumes',
                'expiresAt' => new DateTimeImmutable('+3 days'),
                'user' => $user3,
                'price' => 7.50,
                'type' => self::PREPARED_MEAL,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::VEGAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                ],
            ],
            [
                'title' => 'Jus de Légumes Maison',
                'expiresAt' => new DateTimeImmutable('+2 days'),
                'user' => $user2,
                'price' => 4.25,
                'type' => self::FRUITS,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::VEGAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                ],
            ],
            [
                'title' => 'Cookies Vegan aux Pépites de Chocolat',
                'expiresAt' => new DateTimeImmutable('+7 days'),
                'user' => $user4,
                'price' => 5.99,
                'type' => self::CAKE,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::VEGAN,
                ],
            ],
            [
                'title' => 'Crème Dessert sans Lactose',
                'expiresAt' => new DateTimeImmutable('+5 days'),
                'user' => $user2,
                'price' => 3.99,
                'type' => self::DAIRY_PRODUCT,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                ],
            ],
            [
                'title' => 'Pâtes Fraîches sans Gluten',
                'expiresAt' => new DateTimeImmutable('+4 days'),
                'user' => $user2,
                'price' => 6.50,
                'type' => self::VEGETABLES,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::VEGAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE
                ],
            ],
            [
                'title' => 'Filet de Cabillaud Frais',
                'expiresAt' => new DateTimeImmutable('+2 days'),
                'user' => $user3,
                'price' => 12.99,
                'type' => self::FRUITS,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::PESCATARIAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                    DietaryPreferenceFixtures::LOW_CARB,
                ],
            ],
            [
                'title' => 'Salade de Crevettes à l\'Avocat',
                'expiresAt' => new DateTimeImmutable('+1 day'),
                'user' => $user3,
                'price' => 9.99,
                'type' => self::PREPARED_MEAL,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::PESCATARIAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                    DietaryPreferenceFixtures::LOW_CARB,
                ],
            ],
            [
                'title' => 'Omelette aux Légumes',
                'expiresAt' => new DateTimeImmutable('+2 days'),
                'user' => $user3,
                'price' => 6.49,
                'type' => self::PREPARED_MEAL,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                    DietaryPreferenceFixtures::LOW_CARB,
                ],
            ],
            [
                'title' => 'Gâteau Vegan à la Banane',
                'expiresAt' => new DateTimeImmutable('+4 days'),
                'user' => $user4,
                'price' => 11.99,
                'type' => self::CAKE,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::VEGAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                ],
            ],
            [
                'title' => 'Hummus Maison',
                'expiresAt' => new DateTimeImmutable('+5 days'),
                'user' => $user4,
                'price' => 4.75,
                'type' => self::VEGETABLES,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::VEGAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                ],
            ],
            [
                'title' => 'Lait d\'Amande Fait Maison',
                'expiresAt' => new DateTimeImmutable('+7 days'),
                'user' => $user4,
                'price' => 3.99,
                'type' => self::DAIRY_PRODUCT,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::VEGAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                ],
            ],
            [
                'title' => 'Tajine de Légumes Halal',
                'expiresAt' => new DateTimeImmutable('+3 days'),
                'user' => $user5,
                'price' => 13.50,
                'type' => self::PREPARED_MEAL,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::VEGAN,
                    DietaryPreferenceFixtures::HALAL,
                ],
            ],
            [
                'title' => 'Smoothie Protéiné',
                'expiresAt' => new DateTimeImmutable('+1 day'),
                'user' => $user5,
                'price' => 5.25,
                'type' => self::FRUITS,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                ],
            ],
            [
                'title' => 'Poulet Rôti aux Herbes',
                'expiresAt' => new DateTimeImmutable('+2 days'),
                'user' => $user5,
                'price' => 10.99,
                'type' => self::PREPARED_MEAL,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::HALAL,
                ],
            ],
            [
                'title' => 'Granola Paléo Maison',
                'expiresAt' => new DateTimeImmutable('+14 days'),
                'user' => $user6,
                'price' => 8.75,
                'type' => self::FRUITS,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::PALEO,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                ],
            ],
            [
                'title' => 'Tarte aux Pommes sans Sucre',
                'expiresAt' => new DateTimeImmutable('+3 days'),
                'user' => $user6,
                'price' => 11.50,
                'type' => self::CAKE,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::PALEO,
                ],
            ],
            [
                'title' => 'Bœuf Séché Maison',
                'expiresAt' => new DateTimeImmutable('+21 days'),
                'user' => $user6,
                'price' => 14.99,
                'type' => self::FRUITS,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::PALEO,
                ],
            ],
            [
                'title' => 'Pain Low-Carb aux Graines',
                'expiresAt' => new DateTimeImmutable('+5 days'),
                'user' => $john,
                'price' => 6.75,
                'type' => self::FRUITS,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                    DietaryPreferenceFixtures::LOW_CARB,
                ],
            ],
            [
                'title' => 'Lasagnes Végétariennes',
                'expiresAt' => new DateTimeImmutable('+4 days'),
                'user' => $emma,
                'price' => 9.50,
                'type' => self::PREPARED_MEAL,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                ],
            ],
            [
                'title' => 'Tofu Grillé aux Épices',
                'expiresAt' => new DateTimeImmutable('+7 days'),
                'user' => $admin,
                'price' => 7.25,
                'type' => self::PREPARED_MEAL,
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::VEGAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
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
