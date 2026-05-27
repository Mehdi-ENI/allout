<?php

namespace App\Service;

use App\Entity\Sortie;
use App\Enum\Etat;

/**
 * Service responsable du calcul de l'état métier d'une sortie.
 *
 * Cette classe centralise toute la logique permettant de déterminer
 * l'état actuel d'une sortie selon :
 * - sa date de début
 * - sa durée
 * - sa date limite d'inscription
 * - son statut de publication
 * - son statut d'annulation
 *
 * Etats possibles :
 * - Créée
 * - Publiée
 * - Clôturée
 * - En cours
 * - Terminée
 * - Archivée
 * - Annulée
 */
class SortieStateResolver
{
    /**
     * Détermine l'état actuel d'une sortie.
     *
     * Ordre de priorité :
     * 1. Annulée
     * 2. Créée
     * 3. Publiée
     * 4. Clôturée
     * 5. En cours
     * 6. Terminée
     * 7. Archivée
     *
     * @param Sortie $sortie Sortie à analyser
     *
     * @return Etat Etat métier calculé
     */
    public function resolve(Sortie $sortie): Etat
    {
        /*
         * Une sortie annulée reste prioritaire
         * peu importe les dates.
         */
        if ($sortie->isAnnulee()) {
            return Etat::Annulee;
        }

        $now = new \DateTime();

        /*
         * Calcul de la date de fin :
         * date de début + durée.
         */
        $dateFin = (clone $sortie->getDateHeureDebut())->add($sortie->getDuree());

        /*
         * Une sortie devient archivée
         * 30 jours après sa fin.
         */
        $dateArchivage = (clone $dateFin)->modify('+30 days');

        /*
         * Une sortie inactive est considérée
         * comme "Créée" (brouillon).
         */
        if (!$sortie->isActive()) {
            return Etat::Creee;
        }

        /*
         * Inscriptions encore ouvertes.
         */
        if ($now < $sortie->getDateLimiteInscription()) {
            return Etat::Publiee;
        }

        /*
         * Inscriptions fermées
         * mais sortie pas encore démarrée.
         */
        if ($now > $sortie->getDateLimiteInscription() && $now < $sortie->getDateHeureDebut()) {
            return Etat::Cloturee;
        }

        /*
         * Sortie actuellement en cours.
         */
        if ($now >= $sortie->getDateHeureDebut() && $now <= $dateFin) {
            return Etat::EnCours;
        }

        /*
         * Sortie terminée mais non archivée.
         */
        if ($now > $dateFin && $now <= $dateArchivage) {
            return Etat::Terminee;
        }

        /*
         * Dernier état possible :
         * archivée.
         */
        return Etat::Archivee;
    }
}
