<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public const string REFERENCE_IDENTIFIER = 'user_';

    public function load(ObjectManager $manager): void
    {
        $users = [
            [
                'reference' => self::REFERENCE_IDENTIFIER . 'ADMIN',
                'email' => 'admin@mealmates.fr',
                'firstName' => 'Admin',
                'lastName' => 'Admin',
                'password' => 'xxx',
                'roles' => ['ROLE_ADMIN'],
                'isVerified' => true,
            ],
            [
                'reference' => self::REFERENCE_IDENTIFIER . 'USER',
                'email' => 'user@mealmates.fr',
                'firstName' => 'User',
                'lastName' => 'User',
                'password' => 'xxx',
                'roles' => ['ROLE_USER'],
                'isVerified' => true,
            ],
            [
                'reference' => self::REFERENCE_IDENTIFIER . 'USER2',
                'email' => 'user2@mealmates.fr',
                'firstName' => 'User2',
                'lastName' => 'User',
                'password' => 'xxx',
                'roles' => ['ROLE_USER'],
                'isVerified' => true,
            ],
            [
                'reference' => self::REFERENCE_IDENTIFIER . 'USER3',
                'email' => 'user3@mealmates.fr',
                'firstName' => 'User3',
                'lastName' => 'User',
                'password' => 'xxx',
                'roles' => ['ROLE_USER'],
                'isVerified' => true,
            ],
            [
                'reference' => self::REFERENCE_IDENTIFIER . 'USER4',
                'email' => 'user4@mealmates.fr',
                'firstName' => 'User4',
                'lastName' => 'User',
                'password' => 'xxx',
                'roles' => ['ROLE_USER'],
                'isVerified' => true,
            ],
            [
                'reference' => self::REFERENCE_IDENTIFIER . 'USER5',
                'email' => 'user5@mealmates.fr',
                'firstName' => 'User5',
                'lastName' => 'User',
                'password' => 'xxx',
                'roles' => ['ROLE_USER'],
                'isVerified' => true,
            ],
            [
                'reference' => self::REFERENCE_IDENTIFIER . 'USER6',
                'email' => 'user6@mealmates.fr',
                'firstName' => 'User6',
                'lastName' => 'User',
                'password' => 'xxx',
                'roles' => ['ROLE_USER'],
                'isVerified' => true,
            ],
            [
                'reference' => self::REFERENCE_IDENTIFIER . 'john',
                'email' => 'john.doe@example.com',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'password' => 'password123',
                'roles' => ['ROLE_USER'],
                'isVerified' => true,
            ],
            [
                'reference' => self::REFERENCE_IDENTIFIER . 'emma',
                'email' => 'emma.wilson@example.com',
                'firstName' => 'Emma',
                'lastName' => 'Wilson',
                'password' => 'password123',
                'roles' => ['ROLE_USER'],
                'isVerified' => true,
            ],
        ];

        foreach ($users as $userData) {
            $user = new User();
            $user->setEmail($userData['email']);
            $user->setIsVerified($userData['isVerified']);
            $user->setFirstName($userData['firstName']);
            $user->setLastName($userData['lastName']);
            $user->setRoles($userData['roles']);
            $user->setPassword($this->passwordHasher->hashPassword($user, $userData['password']));

            $manager->persist($user);
            $this->addReference($userData['reference'], $user);
        }

        $manager->flush();
    }
}
