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
        $admin = new User();

        $admin->setEmail('admin@mealmates.fr')
            ->setIsVerified(true)
            ->setFirstName('Admin')
            ->setLastName('Admin')
            ->setRoles(['ROLE_ADMIN'])
            ->setPassword($this->passwordHasher->hashPassword($admin, 'xxx'));
        $this->setReference(self::REFERENCE_IDENTIFIER . 'ADMIN', $admin);

        $user = new User();

        $user->setEmail('user@mealmates.fr')
            ->setIsVerified(true)
            ->setFirstName('User')
            ->setLastName('User')
            ->setRoles(['ROLE_USER'])
            ->setPassword($this->passwordHasher->hashPassword($user, 'xxx'));
        $this->setReference(self::REFERENCE_IDENTIFIER . 'USER', $user);

        $manager->persist($admin);
        $manager->persist($user);

        $manager->flush();
    }
}
