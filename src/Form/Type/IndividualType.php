<?php

namespace App\Form\Type;

use App\Entity\Individual;
use App\Entity\Species;
use App\Entity\Station;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IndividualType extends AbstractType
{
    private $manager;
    private $stationAllSpecies;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->stationAllSpecies = $options['stationAllSpecies'];

        $builder
            ->add('name', TextType::class, ['required' => true])
            ->add('species', EntityType::class, [
                'class' => Species::class,
                'choice_label' => function ($species) {
                    $vernacularName = $species->getVernacularName();
                    if (in_array($species, $this->stationAllSpecies)) {
                        $vernacularName .= ' (+)';
                    }

                    return ucfirst($vernacularName);
                },
                'choice_attr' => function ($species, $key, $speciesId) {
                    $choiceClassAttr = 'species-option species-'.$speciesId;
                    if (in_array($species, $this->stationAllSpecies)) {
                        $choiceClassAttr .= ' exists-in-station';
                    }

                    return ['class' => $choiceClassAttr];
                },
                'attr' => [
                    'required' => true,
                ],
                'placeholder' => 'Choisir une espèce',
            ])
            ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Individual::class,
        ]);

        $resolver->setRequired('stationAllSpecies'); // Requires that currentOrg be set by the caller.
        $resolver->setAllowedTypes('stationAllSpecies', 'array'); // Validates the type(s) of option(s) passed.
    }
}
