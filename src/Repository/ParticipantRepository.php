<?php

namespace App\Repository;

use App\Entity\Participant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * Repository gérant les requêtes en base de données pour l'entité Participant.
 *
 * Un repository, c'est comme un bibliothécaire : tu lui demandes un livre
 * (un participant), il sait exactement où le trouver dans les rayons (la BDD).
 *
 * @extends ServiceEntityRepository<Participant>
 */
class ParticipantRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participant::class);
    }

    /**
     * Met à jour automatiquement le hash du mot de passe d'un utilisateur.
     * Appelé par Symfony quand l'algorithme de hashage évolue.
     *
     * @param PasswordAuthenticatedUserInterface $user             L'utilisateur dont on rehash le mot de passe
     * @param string                             $newHashedPassword Le nouveau mot de passe déjà hashé
     *
     * @throws UnsupportedUserException Si l'utilisateur n'est pas un Participant
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Participant) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Désactive un participant : il ne pourra plus se connecter.
     *
     * On utilise une requête DQL UPDATE directe plutôt que de charger
     * l'entité en mémoire, ce qui est plus performant.
     *
     * @param int $id L'identifiant du participant à désactiver
     */
    public function desactivateParticipant(int $id): void
    {
        $this->createQueryBuilder('p')
            ->update()
            ->set('p.actif', ':actif')
            ->where('p.id = :id')
            ->setParameter('actif', false)
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }

    /**
     * Réactive un participant : il peut à nouveau se connecter.
     *
     * @param int $id L'identifiant du participant à réactiver
     */
    public function activateParticipant(int $id): void
    {
        $this->createQueryBuilder('p')
            ->update()
            ->set('p.actif', ':actif')
            ->where('p.id = :id')
            ->setParameter('actif', true)
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }
}
