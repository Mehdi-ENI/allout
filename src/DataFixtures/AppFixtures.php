<?php

namespace App\DataFixtures;

use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Site;
use App\Entity\Ville;
use App\Enum\Etat;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use App\Entity\Sortie;

class AppFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $site = new Site();
        $site->setNom('Test');
        $manager->persist($site);

//        $ville = new Ville();
//        $ville->setNom('Test');
//        $ville->setCodePostal('12345');
//        $manager->persist($ville);
//
//        $lieu = new Lieu();
//        $lieu->setNom('Test');
//        $lieu->setRue('Test');
//        $lieu->setVille($ville);
//        $manager->persist($lieu);

        $organisateur1 = new Participant();
        $organisateur1->setNom('Dumitrescu')
            ->setPrenom('Diana')
            ->setEmail('dd@test.com')
            ->setPassword(password_hash('test', PASSWORD_BCRYPT))
            ->setActif(true)
            ->setTelephone('0123456789')
            ->setRoles(['ROLE_USER'])
            ->setSite($site);
        $manager->persist($organisateur1);

        $organisateur2 = new Participant();
        $organisateur2->setNom('Forveille')
            ->setPrenom('Erwan')
            ->setEmail('ef@test.com')
            ->setPassword(password_hash('test', PASSWORD_BCRYPT))
            ->setActif(true)
            ->setTelephone('0123456789')
            ->setRoles(['ROLE_USER'])
            ->setSite($site);
        $manager->persist($organisateur2);

        $organisateur3 = new Participant();
        $organisateur3->setNom('Rochereau')
            ->setPrenom('Mehdi')
            ->setEmail('mr@test.com')
            ->setPassword(password_hash('test', PASSWORD_BCRYPT))
            ->setActif(true)
            ->setTelephone('0123456789')
            ->setRoles(['ROLE_USER'])
            ->setSite($site);
        $manager->persist($organisateur3);

        $organisateur4 = new Participant();
        $organisateur4->setNom('Tieliehin')
            ->setPrenom('Mykhailo')
            ->setEmail('mt@test.com')
            ->setPassword(password_hash('test', PASSWORD_BCRYPT))
            ->setActif(true)
            ->setTelephone('0123456789')
            ->setRoles(['ROLE_USER'])
            ->setSite($site);
        $manager->persist($organisateur4);

        $admin = new Participant();
        $admin->setNom('Admin')
            ->setPrenom('Admin')
            ->setEmail('admin@test.com')
            ->setPassword(password_hash('admin', PASSWORD_BCRYPT))
            ->setActif(true)
            ->setTelephone('0123456789')
            ->setRoles(['ROLE_ADMIN'])
            ->setSite($site);
        $manager->persist($admin);

        $manager->flush();

        $faker = Factory::create('fr_FR');
        $lieu = $manager->getRepository(Lieu::class)->findAll(); // Récupère les lieux
        for ($i = 0; $i < 20; $i++) {

            $dateLimiteInscription = $faker->dateTimeBetween('-1 month', '+1 month');
            $dateHeureDebut = $faker->dateTimeBetween($dateLimiteInscription, '+2 months');
            $organisateur = $faker->randomElement([$organisateur1, $organisateur2, $organisateur3, $organisateur4]);
            $autresParticipants = array_filter(
                [$organisateur1, $organisateur2, $organisateur3, $organisateur4],
                fn($p) => $p !== $organisateur
            );

            $sortie = new Sortie();
            $sortie
                ->setNom($faker->sentence(3))
                ->setDuree(new \DateInterval('PT' . max(1, $faker->numberBetween(1, 5)) . 'H'))
                ->setDateLimiteInscription($dateLimiteInscription)
                ->setDateHeureDebut($dateHeureDebut)
                ->setNbInscriptionsMax($faker->numberBetween(10, 20))
                ->setInfosSortie($faker->paragraph())
                ->setOrganisateur($organisateur)
                ->addParticipant($faker->randomElement($autresParticipants))
                ->setSite($site)
                ->setLieu($faker->randomElement($lieu))
                ->setActive($faker->boolean(80)) // 80% de chances d'être active
                ->setAnnulee($faker->boolean(20)); // 20% de chances d'être annulée

            $manager->persist($sortie);
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            // TODO SiteFixtures::class,
            VilleFixtures::class,
            LieuFixtures::class,
            //TODO ParticipantFixtures::class,
        ];
    }
}
