<?php

namespace App\Repository;

use App\Entity\Sortie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @extends ServiceEntityRepository<Sortie>
 */
class SortieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry           $registry,
                                private readonly Security $security)
    {
        parent::__construct($registry, Sortie::class);
    }

    public function findWithFilters(array $filters): array
    {
        $user = $this->security->getUser();

        $qb = $this->createQueryBuilder('s');

        if (!empty($filters['site'])) {
            $qb->andWhere('s.site = :site')
                ->setParameter('site', $filters['site']);
        }

        if (!empty($filters['nom'])) {
            $qb->andWhere('s.nom LIKE :nom')
                ->setParameter('nom', '%' . $filters['nom'] . '%');
        }

        if (!empty($filters['date_debut']) && empty($filters['date_fin'])) {
            $qb->andWhere('s.dateHeureDebut >= :date')
                ->setParameter('date', $filters['date_debut']);
        }

        if (empty($filters['date_debut']) && !empty($filters['date_fin'])) {
            $qb->andWhere('s.dateHeureDebut <= :date')
                ->setParameter('date', $filters['date_fin']);
        }

        if (!empty($filters['date_debut']) && !empty($filters['date_fin'])) {
            $qb->andWhere('s.dateHeureDebut BETWEEN :date_debut AND :date_fin')
                ->setParameter('date_debut', $filters['date_debut'])
                ->setParameter('date_fin', $filters['date_fin']);
        }

        if (!empty($filters['sorties_organisees']) && $user) {
            $qb->andWhere('s.organisateur = :organisateur')
                ->setParameter('organisateur', $user);
        }

        if (!empty($filters['sorties_inscrits']) && $user) {
            $qb->join('s.participants', 'p')
                ->andWhere('p = :participant')
                ->setParameter('participant', $user);
        }

        if (!empty($filters['sorties_non_inscrits']) && $user) {
            $qb->andWhere(
                $qb->expr()->not(
                    $qb->expr()->isMemberOf(':participant', 's.participants')
                )
            )->setParameter('participant', $user);
        }

        if (!empty($filters['sorties_passees'])) {
            $qb->andWhere('s.dateHeureDebut < :date')
                ->setParameter('date', new \DateTime('NOW'));
        }

        $qb->orderBy('s.dateHeureDebut', 'ASC');

        return $qb
            ->getQuery()
            ->getResult();


    }

//    /**
//     * @return Sortie[] Returns an array of Sortie objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Sortie
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
