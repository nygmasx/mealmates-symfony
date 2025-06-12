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
    public const TYPE_FOOD = 'food';
    public const TYPE_BEVERAGE = 'beverage';
    public const TYPE_BAKERY = 'bakery';
    public const TYPE_DAIRY = 'dairy';
    public const TYPE_OTHER = 'other';

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
                'quantity' => 5,
                'expiresAt' => new DateTimeImmutable('+10 days'),
                'price' => 2.99,
                'user' => $john,
                'type' => self::TYPE_FOOD,
                'images' => ['/uploads/products/pommes1.jpg', '/uploads/products/pommes2.jpg'],
                'availabilities' => [
                    ['day' => 'Lundi', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => true],
                    ['day' => 'Mardi', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => true],
                    ['day' => 'Mercredi', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => false],
                    ['day' => 'Jeudi', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => true],
                    ['day' => 'Vendredi', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => true],
                    ['day' => 'Samedi', 'startTime' => '10:00', 'endTime' => '16:00', 'isEnabled' => true],
                    ['day' => 'Dimanche', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => false]
                ],
                'isRecurring' => false,
                'recurringFrequency' => 'weekly',
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::VEGAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                    DietaryPreferenceFixtures::LACTOSE_FREE,
                ],
            ],
            [
                'title' => 'Bananes Bio',
                'quantity' => 8,
                'expiresAt' => new DateTimeImmutable('+7 days'),
                'user' => $user,
                'price' => 1.99,
                'type' => self::TYPE_FOOD,
                'images' => ['/uploads/products/bananes1.jpg'],
                'availabilities' => [
                    ['day' => 'Lundi', 'startTime' => '08:00', 'endTime' => '20:00', 'isEnabled' => true],
                    ['day' => 'Mardi', 'startTime' => '08:00', 'endTime' => '20:00', 'isEnabled' => true],
                    ['day' => 'Mercredi', 'startTime' => '08:00', 'endTime' => '20:00', 'isEnabled' => true],
                    ['day' => 'Jeudi', 'startTime' => '08:00', 'endTime' => '20:00', 'isEnabled' => true],
                    ['day' => 'Vendredi', 'startTime' => '08:00', 'endTime' => '20:00', 'isEnabled' => true],
                    ['day' => 'Samedi', 'startTime' => '09:00', 'endTime' => '17:00', 'isEnabled' => true],
                    ['day' => 'Dimanche', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => false]
                ],
                'isRecurring' => true,
                'recurringFrequency' => 'weekly',
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::VEGAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                    DietaryPreferenceFixtures::LACTOSE_FREE,
                ],
            ],
            [
                'title' => 'Pain Artisanal au Levain',
                'quantity' => 3,
                'expiresAt' => new DateTimeImmutable('+3 days'),
                'user' => $admin,
                'price' => 3.50,
                'type' => self::TYPE_BAKERY,
                'images' => ['/uploads/products/pain1.jpg', '/uploads/products/pain2.jpg'],
                'availabilities' => [
                    ['day' => 'Lundi', 'startTime' => '07:00', 'endTime' => '19:00', 'isEnabled' => true],
                    ['day' => 'Mardi', 'startTime' => '07:00', 'endTime' => '19:00', 'isEnabled' => true],
                    ['day' => 'Mercredi', 'startTime' => '07:00', 'endTime' => '19:00', 'isEnabled' => true],
                    ['day' => 'Jeudi', 'startTime' => '07:00', 'endTime' => '19:00', 'isEnabled' => true],
                    ['day' => 'Vendredi', 'startTime' => '07:00', 'endTime' => '19:00', 'isEnabled' => true],
                    ['day' => 'Samedi', 'startTime' => '07:00', 'endTime' => '17:00', 'isEnabled' => true],
                    ['day' => 'Dimanche', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => false]
                ],
                'isRecurring' => true,
                'recurringFrequency' => 'daily',
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::VEGAN,
                ],
            ],
            [
                'title' => 'Yaourt Grec Maison',
                'quantity' => 10,
                'expiresAt' => new DateTimeImmutable('+14 days'),
                'user' => $john,
                'price' => 3.49,
                'type' => self::TYPE_DAIRY,
                'images' => ['/uploads/products/yaourt1.jpg'],
                'availabilities' => [
                    ['day' => 'Lundi', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => false],
                    ['day' => 'Mardi', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => true],
                    ['day' => 'Mercredi', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => true],
                    ['day' => 'Jeudi', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => true],
                    ['day' => 'Vendredi', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => true],
                    ['day' => 'Samedi', 'startTime' => '10:00', 'endTime' => '16:00', 'isEnabled' => true],
                    ['day' => 'Dimanche', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => false]
                ],
                'isRecurring' => false,
                'recurringFrequency' => 'weekly',
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                ],
            ],
            [
                'title' => 'Jus d\'Orange Pressé',
                'quantity' => 6,
                'expiresAt' => new DateTimeImmutable('+2 days'),
                'user' => $emma,
                'price' => 4.25,
                'type' => self::TYPE_BEVERAGE,
                'images' => ['/uploads/products/jus1.jpg', '/uploads/products/jus2.jpg'],
                'availabilities' => [
                    ['day' => 'Lundi', 'startTime' => '08:00', 'endTime' => '20:00', 'isEnabled' => true],
                    ['day' => 'Mardi', 'startTime' => '08:00', 'endTime' => '20:00', 'isEnabled' => true],
                    ['day' => 'Mercredi', 'startTime' => '08:00', 'endTime' => '20:00', 'isEnabled' => true],
                    ['day' => 'Jeudi', 'startTime' => '08:00', 'endTime' => '20:00', 'isEnabled' => true],
                    ['day' => 'Vendredi', 'startTime' => '08:00', 'endTime' => '20:00', 'isEnabled' => true],
                    ['day' => 'Samedi', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => true],
                    ['day' => 'Dimanche', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => false]
                ],
                'isRecurring' => false,
                'recurringFrequency' => 'weekly',
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::VEGAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                ],
            ],
            [
                'title' => 'Gâteau au Chocolat Vegan',
                'quantity' => 1,
                'expiresAt' => new DateTimeImmutable('+5 days'),
                'user' => $user2,
                'price' => 12.99,
                'type' => self::TYPE_BAKERY,
                'images' => ['/uploads/products/gateau1.jpg'],
                'availabilities' => [
                    ['day' => 'Lundi', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => false],
                    ['day' => 'Mardi', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => false],
                    ['day' => 'Mercredi', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => true],
                    ['day' => 'Jeudi', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => true],
                    ['day' => 'Vendredi', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => true],
                    ['day' => 'Samedi', 'startTime' => '10:00', 'endTime' => '17:00', 'isEnabled' => true],
                    ['day' => 'Dimanche', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => true]
                ],
                'isRecurring' => false,
                'recurringFrequency' => 'weekly',
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::VEGAN,
                ],
            ],
            [
                'title' => 'Légumes de Saison Bio',
                'quantity' => 15,
                'expiresAt' => new DateTimeImmutable('+8 days'),
                'user' => $user3,
                'price' => 6.50,
                'type' => self::TYPE_FOOD,
                'images' => ['/uploads/products/legumes1.jpg', '/uploads/products/legumes2.jpg', '/uploads/products/legumes3.jpg'],
                'availabilities' => [
                    ['day' => 'Lundi', 'startTime' => '06:00', 'endTime' => '14:00', 'isEnabled' => true],
                    ['day' => 'Mardi', 'startTime' => '06:00', 'endTime' => '14:00', 'isEnabled' => true],
                    ['day' => 'Mercredi', 'startTime' => '06:00', 'endTime' => '14:00', 'isEnabled' => true],
                    ['day' => 'Jeudi', 'startTime' => '06:00', 'endTime' => '14:00', 'isEnabled' => true],
                    ['day' => 'Vendredi', 'startTime' => '06:00', 'endTime' => '14:00', 'isEnabled' => true],
                    ['day' => 'Samedi', 'startTime' => '06:00', 'endTime' => '15:00', 'isEnabled' => true],
                    ['day' => 'Dimanche', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => false]
                ],
                'isRecurring' => true,
                'recurringFrequency' => 'weekly',
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::VEGAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                    DietaryPreferenceFixtures::LACTOSE_FREE,
                ],
            ],
            [
                'title' => 'Fromage de Chèvre Fermier',
                'quantity' => 4,
                'expiresAt' => new DateTimeImmutable('+20 days'),
                'user' => $user4,
                'price' => 8.99,
                'type' => self::TYPE_DAIRY,
                'images' => ['/uploads/products/fromage1.jpg'],
                'availabilities' => [
                    ['day' => 'Lundi', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => true],
                    ['day' => 'Mardi', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => true],
                    ['day' => 'Mercredi', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => false],
                    ['day' => 'Jeudi', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => true],
                    ['day' => 'Vendredi', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => true],
                    ['day' => 'Samedi', 'startTime' => '09:00', 'endTime' => '17:00', 'isEnabled' => true],
                    ['day' => 'Dimanche', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => false]
                ],
                'isRecurring' => false,
                'recurringFrequency' => 'weekly',
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                ],
            ],
            [
                'title' => 'Smoothie Vert Détox',
                'quantity' => 8,
                'expiresAt' => new DateTimeImmutable('+1 day'),
                'user' => $user5,
                'price' => 5.25,
                'type' => self::TYPE_BEVERAGE,
                'images' => ['/uploads/products/smoothie1.jpg', '/uploads/products/smoothie2.jpg'],
                'availabilities' => [
                    ['day' => 'Lundi', 'startTime' => '07:00', 'endTime' => '15:00', 'isEnabled' => true],
                    ['day' => 'Mardi', 'startTime' => '07:00', 'endTime' => '15:00', 'isEnabled' => true],
                    ['day' => 'Mercredi', 'startTime' => '07:00', 'endTime' => '15:00', 'isEnabled' => true],
                    ['day' => 'Jeudi', 'startTime' => '07:00', 'endTime' => '15:00', 'isEnabled' => true],
                    ['day' => 'Vendredi', 'startTime' => '07:00', 'endTime' => '15:00', 'isEnabled' => true],
                    ['day' => 'Samedi', 'startTime' => '08:00', 'endTime' => '14:00', 'isEnabled' => true],
                    ['day' => 'Dimanche', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => false]
                ],
                'isRecurring' => true,
                'recurringFrequency' => 'daily',
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::VEGAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                ],
            ],
            [
                'title' => 'Miel de Fleurs Sauvages',
                'quantity' => 12,
                'expiresAt' => new DateTimeImmutable('+365 days'), // Le miel ne périme pratiquement jamais
                'user' => $user6,
                'price' => 9.50,
                'type' => self::TYPE_OTHER,
                'images' => ['/uploads/products/miel1.jpg'],
                'availabilities' => [
                    ['day' => 'Lundi', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => false],
                    ['day' => 'Mardi', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => true],
                    ['day' => 'Mercredi', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => true],
                    ['day' => 'Jeudi', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => true],
                    ['day' => 'Vendredi', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => true],
                    ['day' => 'Samedi', 'startTime' => '09:00', 'endTime' => '16:00', 'isEnabled' => true],
                    ['day' => 'Dimanche', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => false]
                ],
                'isRecurring' => false,
                'recurringFrequency' => 'monthly',
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                ],
            ],
            [
                'title' => 'Pain de la Veille (Gratuit)',
                'quantity' => 5,
                'expiresAt' => new DateTimeImmutable('+1 day'),
                'user' => $admin,
                'price' => 0,
                'type' => self::TYPE_BAKERY,
                'images' => ['/uploads/products/pain_gratuit1.jpg'],
                'availabilities' => [
                    ['day' => 'Lundi', 'startTime' => '18:00', 'endTime' => '19:00', 'isEnabled' => true],
                    ['day' => 'Mardi', 'startTime' => '18:00', 'endTime' => '19:00', 'isEnabled' => true],
                    ['day' => 'Mercredi', 'startTime' => '18:00', 'endTime' => '19:00', 'isEnabled' => true],
                    ['day' => 'Jeudi', 'startTime' => '18:00', 'endTime' => '19:00', 'isEnabled' => true],
                    ['day' => 'Vendredi', 'startTime' => '18:00', 'endTime' => '19:00', 'isEnabled' => true],
                    ['day' => 'Samedi', 'startTime' => '17:00', 'endTime' => '18:00', 'isEnabled' => true],
                    ['day' => 'Dimanche', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => false]
                ],
                'isRecurring' => true,
                'recurringFrequency' => 'daily',
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::VEGAN,
                ],
            ],
            [
                'title' => 'Légumes Invendus (Don)',
                'quantity' => 20,
                'expiresAt' => new DateTimeImmutable('+2 days'),
                'user' => $user3,
                'price' => 0, // Donation
                'type' => self::TYPE_FOOD,
                'images' => ['/uploads/products/legumes_don1.jpg'],
                'availabilities' => [
                    ['day' => 'Lundi', 'startTime' => '17:00', 'endTime' => '18:00', 'isEnabled' => false],
                    ['day' => 'Mardi', 'startTime' => '17:00', 'endTime' => '18:00', 'isEnabled' => false],
                    ['day' => 'Mercredi', 'startTime' => '17:00', 'endTime' => '18:00', 'isEnabled' => false],
                    ['day' => 'Jeudi', 'startTime' => '17:00', 'endTime' => '18:00', 'isEnabled' => false],
                    ['day' => 'Vendredi', 'startTime' => '17:00', 'endTime' => '18:00', 'isEnabled' => false],
                    ['day' => 'Samedi', 'startTime' => '14:00', 'endTime' => '15:00', 'isEnabled' => true],
                    ['day' => 'Dimanche', 'startTime' => '09:00', 'endTime' => '18:00', 'isEnabled' => false]
                ],
                'isRecurring' => true,
                'recurringFrequency' => 'weekly',
                'dietaryPreferences' => [
                    DietaryPreferenceFixtures::VEGETARIAN,
                    DietaryPreferenceFixtures::VEGAN,
                    DietaryPreferenceFixtures::GLUTEN_FREE,
                    DietaryPreferenceFixtures::LACTOSE_FREE,
                ],
            ],
        ];

        foreach ($products as $productData) {
            $product = new Product();
            $product->setTitle($productData['title'])
                ->setQuantity($productData['quantity'])
                ->setExpiresAt($productData['expiresAt'])
                ->setPrice($productData['price'])
                ->setType($productData['type'])
                ->setImages(json_encode($productData['images']))
                ->setAvailabilities($productData['availabilities'])
                ->setIsRecurring($productData['isRecurring'])
                ->setRecurringFrequency($productData['recurringFrequency'])
                ->setUser($productData['user'])
                ->setUpdatedAt(new DateTimeImmutable());

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
