<?php

namespace App\Entity;

use App\Enum\Etat;
use App\Repository\SortieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Représente une sortie organisée par un participant.
 *
 * Cette entité contient toutes les informations métier
 * liées à une sortie :
 * - nom
 * - dates
 * - durée
 * - état métier
 * - participants
 * - organisateur
 * - lieu
 * - publication
 * - annulation
 *
 * L'état d'une sortie est calculé dynamiquement
 * via la méthode getEtat().
 */
#[ORM\Entity(repositoryClass: SortieRepository::class)]
class Sortie
{
    /**
     * Identifiant unique de la sortie.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Nom de la sortie.
     */
    #[Assert\NotBlank(message: "Le nom est obligatoire.")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Le nom doit contenir au moins {{ min }} caractères."
    )]
    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    /**
     * Date et heure de début de la sortie.
     */
    #[Assert\NotNull(message: "La date de début est obligatoire.")]
    #[Assert\GreaterThan(
        "today",
        message: "La date de début doit être dans le futur."
    )]
    #[ORM\Column]
    private ?\DateTime $dateHeureDebut = null;

    /**
     * Durée de la sortie.
     *
     * Stockée sous forme de DateInterval.
     */
    #[ORM\Column]
    private ?\DateInterval $duree = null;

    /**
     * Date limite d'inscription.
     */
    #[Assert\NotNull(message: "La date limite d'inscription est obligatoire.")]
    #[Assert\GreaterThan(
        "today",
        message: "La date limite d'inscription doit être dans le futur."
    )]
    #[Assert\LessThan(
        propertyPath: "dateHeureDebut",
        message: "La date limite d'inscription doit être avant la date de début."
    )]
    #[ORM\Column]
    private ?\DateTime $dateLimiteInscription = null;

    /**
     * Nombre maximum de participants autorisés.
     */
    #[ORM\Column]
    private ?int $nbInscriptionsMax = null;

    /**
     * Informations complémentaires sur la sortie.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $infosSortie = null;

//    /**
//     * Etat métier calculé dynamiquement.
//     *
//     * Cet attribut n'est PAS stocké en base.
//     * Il est recalculé selon :
//     * - les dates
//     * - le statut actif
//     * - l'annulation
//     */
//    private ?Etat $etat = Etat::Creee;

    /**
     * Liste des participants inscrits.
     *
     * @var Collection<int, Participant>
     */
    #[ORM\ManyToMany(
        targetEntity: Participant::class,
        inversedBy: 'sortiesParticipees'
    )]
    private Collection $participants;

    /**
     * Organisateur de la sortie.
     */
    #[ORM\ManyToOne(inversedBy: 'sortiesOrganisees')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Participant $organisateur = null;

    /**
     * Lieu associé à la sortie.
     */
    #[ORM\ManyToOne(inversedBy: 'sorties')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lieu $lieu = null;

    /**
     * Site organisateur de la sortie.
     */
    #[ORM\ManyToOne(inversedBy: 'sorties')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Site $site = null;

    /**
     * Indique si la sortie est publiée.
     *
     * false = brouillon / créée
     * true = publiée
     */
    #[ORM\Column]
    private ?bool $active = false;

    /**
     * Indique si la sortie a été annulée.
     */
    #[ORM\Column]
    private ?bool $annulee = false;

    /**
     * Motif d'annulation de la sortie.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motifAnnulation = null;

    /**
     * Initialise les collections Doctrine.
     */
    public function __construct()
    {
        $this->participants = new ArrayCollection();
    }

    /**
     * Retourne l'identifiant de la sortie.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne le nom de la sortie.
     */
    public function getNom(): ?string
    {
        return $this->nom;
    }

    /**
     * Définit le nom de la sortie.
     */
    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Retourne la date de début.
     */
    public function getDateHeureDebut(): ?\DateTime
    {
        return $this->dateHeureDebut;
    }

    /**
     * Définit la date de début.
     */
    public function setDateHeureDebut(\DateTime $dateHeureDebut): static
    {
        $this->dateHeureDebut = $dateHeureDebut;

        return $this;
    }

    /**
     * Retourne la durée de la sortie.
     */
    public function getDuree(): ?\DateInterval
    {
        return $this->duree;
    }

    /**
     * Définit la durée de la sortie.
     */
    public function setDuree(\DateInterval $duree): static
    {
        $this->duree = $duree;

        return $this;
    }

    /**
     * Retourne la date limite d'inscription.
     */
    public function getDateLimiteInscription(): ?\DateTime
    {
        return $this->dateLimiteInscription;
    }

    /**
     * Définit la date limite d'inscription.
     */
    public function setDateLimiteInscription(\DateTime $dateLimiteInscription): static
    {
        $this->dateLimiteInscription = $dateLimiteInscription;

        return $this;
    }

    /**
     * Retourne le nombre maximal d'inscriptions.
     */
    public function getNbInscriptionsMax(): ?int
    {
        return $this->nbInscriptionsMax;
    }

    /**
     * Définit le nombre maximal d'inscriptions.
     */
    public function setNbInscriptionsMax(int $nbInscriptionsMax): static
    {
        $this->nbInscriptionsMax = $nbInscriptionsMax;

        return $this;
    }

    /**
     * Retourne les informations complémentaires.
     */
    public function getInfosSortie(): ?string
    {
        return $this->infosSortie;
    }

    /**
     * Définit les informations complémentaires.
     */
    public function setInfosSortie(?string $infosSortie): static
    {
        $this->infosSortie = $infosSortie;

        return $this;
    }

//    /**
//     * Calcule dynamiquement l'état de la sortie.
//     *
//     * Ordre de priorité :
//     * 1. Annulée
//     * 2. Créée
//     * 3. Publiée
//     * 4. Clôturée
//     * 5. En cours
//     * 6. Terminée
//     * 7. Archivée
//     */
//    public function getEtat(): Etat
//    {
//        if ($this->annulee) {
//            return Etat::Annulee;
//        }
//
//        $now = new \DateTime();
//
//        $dateFin = (clone $this->dateHeureDebut)
//            ->add($this->duree);
//
//        $dateArchivage = (clone $dateFin)
//            ->modify('+30 days');
//
//        if (!$this->active) {
//            return Etat::Creee;
//        }
//
//        if ($now < $this->dateLimiteInscription) {
//            return Etat::Publiee;
//        }
//
//        if (
//            $now > $this->dateLimiteInscription
//            && $now < $this->dateHeureDebut
//        ) {
//            return Etat::Cloturee;
//        }
//
//        if (
//            $now >= $this->dateHeureDebut
//            && $now <= $dateFin
//        ) {
//            return Etat::EnCours;
//        }
//
//        if (
//            $now > $dateFin
//            && $now <= $dateArchivage
//        ) {
//            return Etat::Terminee;
//        }
//
//        if ($now > $dateArchivage) {
//            return Etat::Archivee;
//        }
//
//        return Etat::Annulee;
//    }

    /**
     * Retourne la liste des participants.
     *
     * @return Collection<int, Participant>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    /**
     * Ajoute un participant à la sortie.
     */
    public function addParticipant(Participant $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
        }

        return $this;
    }

    /**
     * Retire un participant de la sortie.
     */
    public function removeParticipant(Participant $participant): static
    {
        $this->participants->removeElement($participant);

        return $this;
    }

    /**
     * Retourne l'organisateur de la sortie.
     */
    public function getOrganisateur(): ?Participant
    {
        return $this->organisateur;
    }

    /**
     * Définit l'organisateur de la sortie.
     */
    public function setOrganisateur(?Participant $organisateur): static
    {
        $this->organisateur = $organisateur;

        return $this;
    }

    /**
     * Retourne le lieu associé à la sortie.
     */
    public function getLieu(): ?Lieu
    {
        return $this->lieu;
    }

    /**
     * Définit le lieu de la sortie.
     */
    public function setLieu(?Lieu $lieu): static
    {
        $this->lieu = $lieu;

        return $this;
    }

    /**
     * Retourne le site associé à la sortie.
     */
    public function getSite(): ?Site
    {
        return $this->site;
    }

    /**
     * Définit le site associé à la sortie.
     */
    public function setSite(?Site $site): static
    {
        $this->site = $site;

        return $this;
    }

    /**
     * Indique si la sortie est active/publiée.
     */
    public function isActive(): ?bool
    {
        return $this->active;
    }

    /**
     * Définit si la sortie est active/publiée.
     */
    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Indique si la sortie est annulée.
     */
    public function isAnnulee(): ?bool
    {
        return $this->annulee;
    }

    /**
     * Définit le statut d'annulation.
     */
    public function setAnnulee(bool $annulee): static
    {
        $this->annulee = $annulee;

        return $this;
    }

    /**
     * Retourne le motif d'annulation.
     */
    public function getMotifAnnulation(): ?string
    {
        return $this->motifAnnulation;
    }

    /**
     * Définit le motif d'annulation.
     */
    public function setMotifAnnulation(?string $motifAnnulation): static
    {
        $this->motifAnnulation = $motifAnnulation;

        return $this;
    }
}
