<?php

namespace App\DataFixtures;

use App\Entity\Availability;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AvailabilityFixtures extends Fixture
{
    public const MONDAY = 'day-monday';
    public const TUESDAY = 'day-tuesday';
    public const WEDNESDAY = 'day-wednesday';
    public const THURSDAY = 'day-thursday';
    public const FRIDAY = 'day-friday';
    public const SATURDAY = 'day-saturday';
    public const SUNDAY = 'day-sunday';

    public function load(ObjectManager $manager): void
    {
        $days = [
            [
                'reference' => self::MONDAY,
                'name' => 'Monday',
            ],
            [
                'reference' => self::TUESDAY,
                'name' => 'Tuesday',
            ],
            [
                'reference' => self::WEDNESDAY,
                'name' => 'Wednesday',
            ],
            [
                'reference' => self::THURSDAY,
                'name' => 'Thursday',
            ],
            [
                'reference' => self::FRIDAY,
                'name' => 'Friday',
            ],
            [
                'reference' => self::SATURDAY,
                'name' => 'Saturday',
            ],
            [
                'reference' => self::SUNDAY,
                'name' => 'Sunday',
            ],
        ];

        foreach ($days as $dayData) {
            $availability = new Availability();
            $availability->setDayName($dayData['name']);

            $manager->persist($availability);

            $this->addReference($dayData['reference'], $availability);
        }

        $manager->flush();
    }
}
