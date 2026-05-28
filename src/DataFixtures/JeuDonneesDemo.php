<?php

namespace App\DataFixtures;

use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Site;
use App\Entity\Sortie;
use App\Entity\Ville;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Jeu de données de démonstration pour la présentation de l'application.
 *
 * Ordre de chargement :
 *  1. Sites       (pas de dépendances)
 *  2. Villes      (pas de dépendances)
 *  3. Lieux       (dépend de Ville)
 *  4. Participants (dépend de Site)
 *  5. Sorties     (dépend de Lieu, Site, Participant)
 */
class JeuDonneesDemo extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // =====================================================================
        // 1. SITES
        // Un site = un campus / une agence de l'entreprise.
        // On en crée 3 pour montrer que les sorties et participants
        // peuvent appartenir à des sites différents.
        // =====================================================================

        $siteRennes = new Site();
        $siteRennes->setNom('ENI Rennes');

        $siteNantes = new Site();
        $siteNantes->setNom('ENI Nantes');

        $siteParis = new Site();
        $siteParis->setNom('ENI Paris');

        $manager->persist($siteRennes);
        $manager->persist($siteNantes);
        $manager->persist($siteParis);

        // =====================================================================
        // 2. VILLES
        // Codes postaux réels pour la cohérence visuelle en démo.
        // =====================================================================

        $villeRennes = new Ville();
        $villeRennes->setNom('Rennes');
        $villeRennes->setCodePostal('35000');

        $villeNantes = new Ville();
        $villeNantes->setNom('Nantes');
        $villeNantes->setCodePostal('44000');

        $villeParis = new Ville();
        $villeParis->setNom('Paris');
        $villeParis->setCodePostal('75000');

        $villeBrest = new Ville();
        $villeBrest->setNom('Brest');
        $villeBrest->setCodePostal('29200');

        $villeLyon = new Ville();
        $villeLyon->setNom('Lyon');
        $villeLyon->setCodePostal('69000');

        $manager->persist($villeRennes);
        $manager->persist($villeNantes);
        $manager->persist($villeParis);
        $manager->persist($villeBrest);
        $manager->persist($villeLyon);

        // =====================================================================
        // 3. LIEUX
        // Lieux réels avec coordonnées GPS pour montrer une éventuelle carte.
        // On varie les types : bar, parc, salle de sport, restaurant, musée.
        // =====================================================================

        $lieuPinterie = new Lieu();
        $lieuPinterie->setNom('La Cité d\'Ys');
        $lieuPinterie->setRue('2 Rue Saint-Michel');
        $lieuPinterie->setLatitude(48.1113);
        $lieuPinterie->setLongitude(-1.6800);
        $lieuPinterie->setVille($villeRennes);

        $lieuParcRennes = new Lieu();
        $lieuParcRennes->setNom('Parc du Thabor');
        $lieuParcRennes->setRue('Place Saint-Mélaine');
        $lieuParcRennes->setLatitude(48.1158);
        $lieuParcRennes->setLongitude(-1.6724);
        $lieuParcRennes->setVille($villeRennes);

        $lieuBowlingRennes = new Lieu();
        $lieuBowlingRennes->setNom('Bowling Rennes Alma');
        $lieuBowlingRennes->setRue('Centre Commercial Alma');
        $lieuBowlingRennes->setLatitude(48.1005);
        $lieuBowlingRennes->setLongitude(-1.7085);
        $lieuBowlingRennes->setVille($villeRennes);

        $lieuRestaurantNantes = new Lieu();
        $lieuRestaurantNantes->setNom('La Cigale');
        $lieuRestaurantNantes->setRue('4 Place Graslin');
        $lieuRestaurantNantes->setLatitude(47.2133);
        $lieuRestaurantNantes->setLongitude(-1.5631);
        $lieuRestaurantNantes->setVille($villeNantes);

        $lieuParcNantes = new Lieu();
        $lieuParcNantes->setNom('Jardin des Plantes de Nantes');
        $lieuParcNantes->setRue('Rue Stanislas Baudry');
        $lieuParcNantes->setLatitude(47.2181);
        $lieuParcNantes->setLongitude(-1.5423);
        $lieuParcNantes->setVille($villeNantes);

        $lieuParis = new Lieu();
        $lieuParis->setNom('Le Marais - Bar Le Perle');
        $lieuParis->setRue('78 Rue Vieille du Temple');
        $lieuParis->setLatitude(48.8592);
        $lieuParis->setLongitude(2.3584);
        $lieuParis->setVille($villeParis);

        $lieuBrest = new Lieu();
        $lieuBrest->setNom('Océanopolis');
        $lieuBrest->setRue('Port de Plaisance du Moulin Blanc');
        $lieuBrest->setLatitude(48.3786);
        $lieuBrest->setLongitude(-4.4374);
        $lieuBrest->setVille($villeBrest);

        $lieuLyon = new Lieu();
        $lieuLyon->setNom('Parc de la Tête d\'Or');
        $lieuLyon->setRue('Place Général Leclerc');
        $lieuLyon->setLatitude(45.7768);
        $lieuLyon->setLongitude(4.8556);
        $lieuLyon->setVille($villeLyon);

        $manager->persist($lieuPinterie);
        $manager->persist($lieuParcRennes);
        $manager->persist($lieuBowlingRennes);
        $manager->persist($lieuRestaurantNantes);
        $manager->persist($lieuParcNantes);
        $manager->persist($lieuParis);
        $manager->persist($lieuBrest);
        $manager->persist($lieuLyon);

        // =====================================================================
        // 4. PARTICIPANTS
        //
        // Mot de passe unique pour tous en démo : "Password1!"
        // (respecte les contraintes classiques : maj, min, chiffre, spécial)
        //
        // On crée :
        //  - 1 admin (ROLE_ADMIN) sur Rennes
        //  - 1 participant désactivé (actif = false) pour montrer la feature
        //  - 8 participants normaux répartis sur les 3 sites
        // =====================================================================

        $plainPassword = 'Password1!';

        // --- Admin ---
        $admin = new Participant();
        $admin->setEmail('admin@sortir.com');
        $admin->setPrenom('Admin');
        $admin->setNom('Admin');
        $admin->setPseudo('admin');
        $admin->setTelephone('0600000001');
        $admin->setSite($siteRennes);
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $admin->setActif(true);
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, $plainPassword)
        );

        // --- Participant désactivé ---
        $desactive = new Participant();
        $desactive->setEmail('desactive@sortir.com');
        $desactive->setPrenom('Jean');
        $desactive->setNom('Désactivé');
        $desactive->setPseudo('jean_off');
        $desactive->setTelephone('0600000002');
        $desactive->setSite($siteRennes);
        $desactive->setRoles(['ROLE_USER']);
        $desactive->setActif(false); // compte bloqué
        $desactive->setPassword(
            $this->passwordHasher->hashPassword($desactive, $plainPassword)
        );

        // --- Participants Rennes ---
        $alice = new Participant();
        $alice->setEmail('alice@sortir.com');
        $alice->setPrenom('Alice');
        $alice->setNom('Martin');
        $alice->setPseudo('alice_m');
        $alice->setTelephone('0611111111');
        $alice->setSite($siteRennes);
        $alice->setRoles(['ROLE_USER']);
        $alice->setActif(true);
        $alice->setPassword(
            $this->passwordHasher->hashPassword($alice, $plainPassword)
        );

        $bob = new Participant();
        $bob->setEmail('bob@sortir.com');
        $bob->setPrenom('Bob');
        $bob->setNom('Dupont');
        $bob->setPseudo('bob_d');
        $bob->setTelephone('0622222222');
        $bob->setSite($siteRennes);
        $bob->setRoles(['ROLE_USER']);
        $bob->setActif(true);
        $bob->setPassword(
            $this->passwordHasher->hashPassword($bob, $plainPassword)
        );

        $chloe = new Participant();
        $chloe->setEmail('chloe@sortir.com');
        $chloe->setPrenom('Chloé');
        $chloe->setNom('Bernard');
        $chloe->setPseudo('chloe_b');
        $chloe->setTelephone('0633333333');
        $chloe->setSite($siteRennes);
        $chloe->setRoles(['ROLE_USER']);
        $chloe->setActif(true);
        $chloe->setPassword(
            $this->passwordHasher->hashPassword($chloe, $plainPassword)
        );

        // --- Participants Nantes ---
        $david = new Participant();
        $david->setEmail('david@sortir.com');
        $david->setPrenom('David');
        $david->setNom('Leroy');
        $david->setPseudo('david_l');
        $david->setTelephone('0644444444');
        $david->setSite($siteNantes);
        $david->setRoles(['ROLE_USER']);
        $david->setActif(true);
        $david->setPassword(
            $this->passwordHasher->hashPassword($david, $plainPassword)
        );

        $emma = new Participant();
        $emma->setEmail('emma@sortir.com');
        $emma->setPrenom('Emma');
        $emma->setNom('Petit');
        $emma->setPseudo('emma_p');
        $emma->setTelephone('0655555555');
        $emma->setSite($siteNantes);
        $emma->setRoles(['ROLE_USER']);
        $emma->setActif(true);
        $emma->setPassword(
            $this->passwordHasher->hashPassword($emma, $plainPassword)
        );

        // --- Participants Paris ---
        $francois = new Participant();
        $francois->setEmail('francois@sortir.com');
        $francois->setPrenom('François');
        $francois->setNom('Moreau');
        $francois->setPseudo('fra_m');
        $francois->setTelephone('0666666666');
        $francois->setSite($siteParis);
        $francois->setRoles(['ROLE_USER']);
        $francois->setActif(true);
        $francois->setPassword(
            $this->passwordHasher->hashPassword($francois, $plainPassword)
        );

        $gaelle = new Participant();
        $gaelle->setEmail('gaelle@sortir.com');
        $gaelle->setPrenom('Gaëlle');
        $gaelle->setNom('Simon');
        $gaelle->setPseudo('gaelle_s');
        $gaelle->setTelephone('0677777777');
        $gaelle->setSite($siteParis);
        $gaelle->setRoles(['ROLE_USER']);
        $gaelle->setActif(true);
        $gaelle->setPassword(
            $this->passwordHasher->hashPassword($gaelle, $plainPassword)
        );

        $hugo = new Participant();
        $hugo->setEmail('hugo@sortir.com');
        $hugo->setPrenom('Hugo');
        $hugo->setNom('Laurent');
        $hugo->setPseudo('hugo_l');
        $hugo->setTelephone('0688888888');
        $hugo->setSite($siteParis);
        $hugo->setRoles(['ROLE_USER']);
        $hugo->setActif(true);
        $hugo->setPassword(
            $this->passwordHasher->hashPassword($hugo, $plainPassword)
        );

        $manager->persist($admin);
        $manager->persist($desactive);
        $manager->persist($alice);
        $manager->persist($bob);
        $manager->persist($chloe);
        $manager->persist($david);
        $manager->persist($emma);
        $manager->persist($francois);
        $manager->persist($gaelle);
        $manager->persist($hugo);

        // =====================================================================
        // 5. SORTIES
        //
        // On couvre tous les états possibles de l'enum Etat :
        //
        //  État        | active | annulee | dates
        //  ------------|--------|---------|---------------------------
        //  Créée       | false  | false   | futur
        //  Publiée     | true   | false   | dateLimite futur
        //  Clôturée    | true   | false   | dateLimite passée, début futur
        //  En cours    | true   | false   | début passé, fin future
        //  Terminée    | true   | false   | fin passée < 30j
        //  Archivée    | true   | false   | fin passée > 30j
        //  Annulée     | true   | true    | peu importe
        // =====================================================================

        $now = new \DateTime();

        // --- 1. CRÉÉE (brouillon, non publiée) ---
        // Alice a commencé à créer une sortie mais ne l'a pas encore publiée.
        $sortieCreee = new Sortie();
        $sortieCreee->setNom('Soirée jeux de société');
        $sortieCreee->setDateHeureDebut((clone $now)->modify('+15 days')->setTime(19, 0));
        $sortieCreee->setDateLimiteInscription((clone $now)->modify('+10 days')->setTime(23, 59));
        $sortieCreee->setDuree(new \DateInterval('PT3H'));
        $sortieCreee->setNbInscriptionsMax(10);
        $sortieCreee->setInfosSortie('Apportez vos jeux préférés ! Boissons et snacks fournis.');
        $sortieCreee->setActive(false); // brouillon
        $sortieCreee->setAnnulee(false);
        $sortieCreee->setOrganisateur($alice);
        $sortieCreee->setLieu($lieuPinterie);
        $sortieCreee->setSite($siteRennes);

        // --- 2. PUBLIÉE (inscriptions ouvertes) ---
        // Bob organise une rando, inscriptions encore ouvertes.
        $sortiePubliee = new Sortie();
        $sortiePubliee->setNom('Randonnée en forêt de Brocéliande');
        $sortiePubliee->setDateHeureDebut((clone $now)->modify('+20 days')->setTime(9, 0));
        $sortiePubliee->setDateLimiteInscription((clone $now)->modify('+14 days')->setTime(23, 59));
        $sortiePubliee->setDuree(new \DateInterval('PT6H'));
        $sortiePubliee->setNbInscriptionsMax(15);
        $sortiePubliee->setInfosSortie('Niveau facile, 8 km. Prévoir bonnes chaussures et pique-nique.');
        $sortiePubliee->setActive(true);
        $sortiePubliee->setAnnulee(false);
        $sortiePubliee->setOrganisateur($bob);
        $sortiePubliee->setLieu($lieuParcRennes);
        $sortiePubliee->setSite($siteRennes);
        // Quelques inscrits pour montrer la liste des participants
        $sortiePubliee->addParticipant($alice);
        $sortiePubliee->addParticipant($chloe);
        $sortiePubliee->addParticipant($david);

        // --- 3. CLÔTURÉE (date limite dépassée, sortie pas encore commencée) ---
        // David organise un bowling, les inscriptions sont fermées.
        $sortieCloturee = new Sortie();
        $sortieCloturee->setNom('Soirée bowling inter-sites');
        $sortieCloturee->setDateHeureDebut((clone $now)->modify('+3 days')->setTime(20, 0));
        $sortieCloturee->setDateLimiteInscription((clone $now)->modify('-1 days')->setTime(23, 59));
        $sortieCloturee->setDuree(new \DateInterval('PT2H'));
        $sortieCloturee->setNbInscriptionsMax(20);
        $sortieCloturee->setInfosSortie('Tournoi en équipes de 4. Chaussures de bowling fournies.');
        $sortieCloturee->setActive(true);
        $sortieCloturee->setAnnulee(false);
        $sortieCloturee->setOrganisateur($david);
        $sortieCloturee->setLieu($lieuBowlingRennes);
        $sortieCloturee->setSite($siteNantes);
        $sortieCloturee->addParticipant($emma);
        $sortieCloturee->addParticipant($bob);
        $sortieCloturee->addParticipant($francois);
        $sortieCloturee->addParticipant($gaelle);

        // --- 4. EN COURS (sortie démarrée, pas encore terminée) ---
        // Emma organise une visite au Jardin des Plantes commencée il y a 1h.
        $sortieEnCours = new Sortie();
        $sortieEnCours->setNom('Visite Jardin des Plantes Nantes');
        $sortieEnCours->setDateHeureDebut((clone $now)->modify('-1 hour'));
        $sortieEnCours->setDateLimiteInscription((clone $now)->modify('-5 days')->setTime(23, 59));
        $sortieEnCours->setDuree(new \DateInterval('PT4H'));
        $sortieEnCours->setNbInscriptionsMax(12);
        $sortieEnCours->setInfosSortie('Visite guidée de la serre tropicale incluse.');
        $sortieEnCours->setActive(true);
        $sortieEnCours->setAnnulee(false);
        $sortieEnCours->setOrganisateur($emma);
        $sortieEnCours->setLieu($lieuParcNantes);
        $sortieEnCours->setSite($siteNantes);
        $sortieEnCours->addParticipant($david);
        $sortieEnCours->addParticipant($alice);

        // --- 5. TERMINÉE (finie il y a 5 jours, pas encore archivée) ---
        // François a organisé une soirée à Paris la semaine dernière.
        $sortieTerminee = new Sortie();
        $sortieTerminee->setNom('Afterwork Le Marais');
        $sortieTerminee->setDateHeureDebut((clone $now)->modify('-5 days')->setTime(18, 0));
        $sortieTerminee->setDateLimiteInscription((clone $now)->modify('-10 days')->setTime(23, 59));
        $sortieTerminee->setDuree(new \DateInterval('PT3H'));
        $sortieTerminee->setNbInscriptionsMax(8);
        $sortieTerminee->setInfosSortie('Premier verre offert par l\'organisateur !');
        $sortieTerminee->setActive(true);
        $sortieTerminee->setAnnulee(false);
        $sortieTerminee->setOrganisateur($francois);
        $sortieTerminee->setLieu($lieuParis);
        $sortieTerminee->setSite($siteParis);
        $sortieTerminee->addParticipant($gaelle);
        $sortieTerminee->addParticipant($hugo);

        // --- 6. ARCHIVÉE (terminée il y a plus de 30 jours) ---
        // Gaëlle avait organisé une visite à Océanopolis il y a 2 mois.
        $sortieArchivee = new Sortie();
        $sortieArchivee->setNom('Visite Océanopolis Brest');
        $sortieArchivee->setDateHeureDebut((clone $now)->modify('-60 days')->setTime(10, 0));
        $sortieArchivee->setDateLimiteInscription((clone $now)->modify('-65 days')->setTime(23, 59));
        $sortieArchivee->setDuree(new \DateInterval('PT5H'));
        $sortieArchivee->setNbInscriptionsMax(25);
        $sortieArchivee->setInfosSortie('Entrée groupe négociée à tarif réduit.');
        $sortieArchivee->setActive(true);
        $sortieArchivee->setAnnulee(false);
        $sortieArchivee->setOrganisateur($gaelle);
        $sortieArchivee->setLieu($lieuBrest);
        $sortieArchivee->setSite($siteParis);
        $sortieArchivee->addParticipant($francois);
        $sortieArchivee->addParticipant($hugo);
        $sortieArchivee->addParticipant($emma);

        // --- 7. ANNULÉE ---
        // Hugo avait prévu une sortie à Lyon mais l'a annulée (météo).
        $sortieAnnulee = new Sortie();
        $sortieAnnulee->setNom('Pique-nique Parc de la Tête d\'Or');
        $sortieAnnulee->setDateHeureDebut((clone $now)->modify('+7 days')->setTime(12, 0));
        $sortieAnnulee->setDateLimiteInscription((clone $now)->modify('+3 days')->setTime(23, 59));
        $sortieAnnulee->setDuree(new \DateInterval('PT4H'));
        $sortieAnnulee->setNbInscriptionsMax(20);
        $sortieAnnulee->setInfosSortie('Prévoir nappe et panier repas.');
        $sortieAnnulee->setActive(true);
        $sortieAnnulee->setAnnulee(true);
        $sortieAnnulee->setMotifAnnulation('Prévisions météo défavorables pour la semaine. Report à une date ultérieure.');
        $sortieAnnulee->setOrganisateur($hugo);
        $sortieAnnulee->setLieu($lieuLyon);
        $sortieAnnulee->setSite($siteParis);
        $sortieAnnulee->addParticipant($francois);
        $sortieAnnulee->addParticipant($gaelle);

        // --- 8. PUBLIÉE complète (pour tester le refus d'inscription) ---
        // Chloé organise une sortie restaurant avec max 3 places, déjà pleine.
        $sortieComplete = new Sortie();
        $sortieComplete->setNom('Dîner La Cigale Nantes');
        $sortieComplete->setDateHeureDebut((clone $now)->modify('+25 days')->setTime(20, 0));
        $sortieComplete->setDateLimiteInscription((clone $now)->modify('+18 days')->setTime(23, 59));
        $sortieComplete->setDuree(new \DateInterval('PT2H30M'));
        $sortieComplete->setNbInscriptionsMax(3); // volontairement petit pour tester sortie complète
        $sortieComplete->setInfosSortie('Réservation au nom de l\'organisatrice. Menu à 35€ par personne.');
        $sortieComplete->setActive(true);
        $sortieComplete->setAnnulee(false);
        $sortieComplete->setOrganisateur($chloe);
        $sortieComplete->setLieu($lieuRestaurantNantes);
        $sortieComplete->setSite($siteRennes);
        $sortieComplete->addParticipant($alice);
        $sortieComplete->addParticipant($bob);
        $sortieComplete->addParticipant($david); // 3/3 = complet

        $manager->persist($sortieCreee);
        $manager->persist($sortiePubliee);
        $manager->persist($sortieCloturee);
        $manager->persist($sortieEnCours);
        $manager->persist($sortieTerminee);
        $manager->persist($sortieArchivee);
        $manager->persist($sortieAnnulee);
        $manager->persist($sortieComplete);

        $manager->flush();
    }
}
