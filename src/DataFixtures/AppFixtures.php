<?php

namespace App\DataFixtures;

use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Site;
use App\Entity\Ville;
use App\Enum\Etat;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use App\Entity\Sortie;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        $site = new Site();
        $site->setNom('Test');

        $manager->persist($site);
        $manager->flush();

        $ville = new Ville();
        $ville->setNom('Test');
        $ville->setCodePostal('12345');
        $manager->persist($ville);
        $manager->flush();

        $lieu = new Lieu();
        $lieu->setNom('Test');
        $lieu->setRue('Test');
        $lieu->setVille($ville);
        $manager->persist($lieu);
        $manager->flush();



        $organisateur = new Participant();
        $organisateur->setNom('Test');
        $organisateur->setPrenom('User');
        $organisateur->setEmail('orga@test.com');
        $organisateur->setPassword(password_hash('test', PASSWORD_BCRYPT));
        $organisateur->setActif(true);
        $organisateur->setTelephone('0123456789');

        $organisateur->setRoles(['ROLE_USER']);
        $organisateur->setActif(true);
        $organisateur->setSite($site);

        $manager->persist($organisateur);



        $faker = Factory::create('fr_FR');



        for ($i = 0; $i < 20; $i++) {

            $sortie = new Sortie();
            $sortie->setNom($faker->sentence(3))
                ->setDateHeureDebut($faker->dateTimeBetween('-1 month', '+1 month'))
                ->setDuree(new \DateInterval('PT' . max(1, $faker->numberBetween(1, 5)) . 'H')) // Durée en minutes
                ->setDateLimiteInscription($faker->dateTimeBetween('-1 month', '+1 month'))
                ->setNbInscriptionsMax($faker->numberBetween(10, 100))
                ->setInfosSortie($faker->paragraph())
                ->setEtat($faker->randomElement(Etat::cases()))
                ->setOrganisateur($organisateur);
            $sortie->setSite($site);
            dump($sortie->getSite());
            $sortie->setLieu($lieu);

            $manager->persist($sortie);
        }
        $manager->flush();
    }
}
