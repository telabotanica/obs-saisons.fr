<?php

namespace App\Form;

use App\Entity\Individual;
use App\Entity\Species;
use App\Entity\Station;
use App\Service\UploadService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class IndividualType extends AbstractType
{
    private $manager;
    private $stationAllSpecies;
    private $security;
    private $station;
    private $individuals;

    public function __construct(
        ManagerRegistry $manager,
        Security $security,
        UploadService $uploadFileService
    ) {
        $this->manager = $manager;
        $this->security = $security;
        $this->isEdit = false;
        $this->station = null;
        $this->individuals = [];

    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->stationAllSpecies = [];
        if (empty($this->individuals) && isset($options['station'])) {
            $this->station = $options['station'];
            self::setIndividuals($this->station);
        }
        self::setAllSpecies();

        $builder
            ->add('name', TextType::class, ['required' => true])
            ->add('species', EntityType::class, [
                'class' => Species::class,
                'choice_label' => function (Species $species) {
                    $vernacularName = $species->getVernacularName();
                    if (in_array($species, $this->stationAllSpecies)) {
                        $vernacularName .= ' (+)';
                    }

                    return ucfirst($vernacularName);
                },
                'choice_attr' => function (Species $species, $key, $speciesId) {
                    $choiceAttr['class'] = 'species-option species-'.$speciesId;
                    if (in_array($species, $this->stationAllSpecies)) {
                        $choiceAttr['class'] .= ' exists-in-station';

                        if ('plantes' !== $species->getType()->getReign()) {
                            $choiceAttr['disabled'] = 'disabled';
                        }
                    }

                    return $choiceAttr;
                },
                'attr' => [
                    'required' => true,
                ],
                'placeholder' => 'Choisir une espèce',
            ])
            ->add('submit', SubmitType::class)
        ;

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            [$this, 'onPreSetData']
        );

        $builder->addEventListener(
            FormEvents::SUBMIT,
            [$this, 'onSubmit']
        );
    }

    private function setAllSpecies(): self
    {
        foreach ($this->individuals as $individual) {
            $species = $individual->getSpecies();
            if (!in_array($species, $this->stationAllSpecies)) {
                $this->stationAllSpecies[] = $species;
            }
        }

        return $this;
    }

    public function onPreSetData(FormEvent $formEvent)
    {
        $individual = $formEvent->getData();
        if ($individual && null !== $individual->getId()) {
            $this->isEdit = true;
            $this->station = $individual->getStation();
            self::setIndividuals($this->station);
        }
    }

    public function onSubmit(FormEvent $formEvent)
    {
        $individual = $formEvent->getData();

        $species = $this->manager->getRepository(Species::class)
            ->find($individual->getSpecies())
        ;
        $individual->setSpecies($species);

        if (!$this->isEdit) {
            $individual->setStation($this->station);
            $individual->setUser($this->security->getUser());
        }

        $formEvent->setData($individual);
    }

    private function setIndividuals(Station $station): self
    {
        $this->individuals = $this->manager->getRepository(Individual::class)
            ->findSpeciesIndividualsForStation($station)
        ;

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Individual::class,
        ]);

        $resolver->setDefined('station');

        $resolver->setAllowedTypes('station', 'App\\Entity\\Station');
    }
}
