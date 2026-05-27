<?php

namespace App\Form;

use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Site;
use App\Entity\Sortie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortieType extends AbstractType
{

    public function __construct(
        private readonly Security $security
    ) {
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la sortie',
            ])

            ->add('dateHeureDebut', DateTimeType::class, [
                'label' => 'Date et heure de début',
                'widget' => 'single_text',
            ])

            ->add('duree', DateIntervalType::class, [
                'label' => 'Durée',
                'with_years' => false,
                'with_months' => false,
                'with_days' => true,
                'with_hours' => true,
                'with_minutes' => true,
                'with_seconds' => false,
            ])

            ->add('dateLimiteInscription', DateTimeType::class, [
                'label' => 'Date limite d\'inscription',
                'widget' => 'single_text',
            ])

            ->add('nbInscriptionsMax', IntegerType::class, [
                'label' => 'Nombre maximal de participants',
            ])

            ->add('infosSortie', TextareaType::class, [
                'label' => 'Informations',
                'required' => false,
            ])
//            ->add('organisateur', EntityType::class, [
//                'class' => Participant::class,
//                'choice_label' => 'id',
//                'placeholder' => 'Choisissez un organisateur',
//                'label' => 'Organisateur',
//            ])//--------- c'est l'utilisateur connecté-----------
            ->add('lieu', EntityType::class, [
                'class' => Lieu::class,
                'choice_label' => 'nom',
                'choice_attr' => function (Lieu $lieu) {
                    return [
                        'data-lat' => $lieu->getLatitude(),
                        'data-lng' => $lieu->getLongitude(),
                    ];
                },
                'placeholder' => 'Choisir un lieu',
                'label' => 'Lieu',
            ]);
        /**
         * Seul un administrateur peut choisir le site.
         * Pour les autres utilisateurs, le site est défini automatiquement
         * dans le controller à partir du profil connecté.
         */
        if ($this->security->isGranted('ROLE_ADMIN')) {

            $builder->add('site', EntityType::class, [
                'class' => Site::class,
                'choice_label' => 'nom',
                'placeholder' => 'Choisir un site',
            ]);
        }

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
        ]);
    }
}
