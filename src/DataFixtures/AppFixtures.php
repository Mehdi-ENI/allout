<?php

namespace App\DataFixtures;

use App\Enum\Etat;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use App\Entity\Sortie;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < 20; $i++) {

            $sortie = new Sortie();
            $sortie->setNom($faker->sentence(3))
                ->setDateHeureDebut($faker->dateTimeBetween('-1 month', '+1 month'))
                ->setDuree(new \DateInterval('PT' . max(1, $faker->numberBetween(1, 5)) . 'H')) // Durée en minutes
                ->setDateLimiteInscription($faker->dateTimeBetween('-1 month', '+1 month'))
                ->setNbInscriptionsMax($faker->numberBetween(10, 100))
                ->setInfosSortie($faker->paragraph())
                ->setEtat($faker->randomElement(Etat::cases()));
            $manager->persist($sortie);
        }
        $manager->flush();
    }
}
