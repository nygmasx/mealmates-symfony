<?php

namespace App\DataFixtures;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use DateTimeImmutable;

class OrderFixtures extends Fixture implements DependentFixtureInterface
{
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

        $products = $manager->getRepository(Product::class)->findAll();

        $orderData = [
            [
                'user' => $user,
                'products' => [0, 3, 5],
                'createdAt' => new DateTimeImmutable('-30 days'),
            ],
            [
                'user' => $john,
                'products' => [1, 2, 8],
                'createdAt' => new DateTimeImmutable('-25 days'),
            ],
            [
                'user' => $emma,
                'products' => [4, 6],
                'createdAt' => new DateTimeImmutable('-20 days'),
            ],
            [
                'user' => $admin,
                'products' => [7, 9, 11],
                'createdAt' => new DateTimeImmutable('-15 days'),
            ],
            [
                'user' => $user2,
                'products' => [10, 12, 13],
                'createdAt' => new DateTimeImmutable('-10 days'),
            ],
            [
                'user' => $user3,
                'products' => [15, 16, 17],
                'createdAt' => new DateTimeImmutable('-9 days'),
            ],
            [
                'user' => $user4,
                'products' => [14, 20],
                'createdAt' => new DateTimeImmutable('-8 days'),
            ],
            [
                'user' => $user5,
                'products' => [21, 23, 24],
                'createdAt' => new DateTimeImmutable('-7 days'),
            ],
            [
                'user' => $user6,
                'products' => [25, 26],
                'createdAt' => new DateTimeImmutable('-6 days'),
            ],
            [
                'user' => $user,
                'products' => [18, 19, 22],
                'createdAt' => new DateTimeImmutable('-5 days'),
            ],
            [
                'user' => $john,
                'products' => [27, 28],
                'createdAt' => new DateTimeImmutable('-4 days'),
            ],
            [
                'user' => $emma,
                'products' => [29, 2],
                'createdAt' => new DateTimeImmutable('-3 days'),
            ],
            [
                'user' => $admin,
                'products' => [0, 10, 20],
                'createdAt' => new DateTimeImmutable('-2 days'),
            ],
            [
                'user' => $user2,
                'products' => [5, 15, 25],
                'createdAt' => new DateTimeImmutable('-1 days'),
            ],
            [
                'user' => $user3,
                'products' => [1, 11, 21],
                'createdAt' => new DateTimeImmutable('now'),
            ],
        ];

        foreach ($orderData as $data) {
            $order = new Order();
            $order->setUser($data['user']);
            $order->setCreatedAt($data['createdAt']);

            foreach ($data['products'] as $productIndex) {
                if (isset($products[$productIndex])) {
                    $order->addProduct($products[$productIndex]);
                }
            }

            $manager->persist($order);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            ProductFixtures::class,
        ];
    }
}
