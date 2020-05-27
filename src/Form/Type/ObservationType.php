<?php

namespace App\Form\Type;

use App\Entity\Event;
use App\Entity\EventSpecies;
use App\Entity\Individual;
use App\Entity\Observation;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ObservationType extends AbstractType
{
    private $individuals;
    private $events;
    private $manager;

    public function __construct(ManagerRegistry $manager)
    {
        $this->manager = $manager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->individuals = $options['individuals'];
        $selectIndividualClassAttr = 'select-field';
        if (1 === count($this->individuals)) {
            $selectIndividualClassAttr .= ' disabled';
        }
        $allSpecies = [];
        foreach ($this->individuals as $individual) {
            $species = $individual->getSpecies();
            if (!in_array($species, $allSpecies)) {
                $allSpecies[] = $species;
            }
        }
        $this->events = [];
        foreach ($allSpecies as $species) {
            $eventSpeciesForSpecies = $this->manager->getRepository(EventSpecies::class)
                ->findBy(['species' => $species])
            ;
            foreach ($eventSpeciesForSpecies as $eventSpecies) {
                $event = $eventSpecies->getEvent();
                if (!in_array($event, $this->events)) {
                    $this->events[] = $event;
                }
            }
        }

        $selectEventClassAttr = 'select-field';
        if (1 === count($this->events)) {
            $selectEventClassAttr .= ' disabled';
        }

        $builder
            ->add('individual', EntityType::class, [
                'class' => Individual::class,
                'attr' => [
                    'class' => $selectIndividualClassAttr,
                    'required' => true,
                ],
                'choices' => $this->individuals,
                'choice_label' => 'name',
                'choice_attr' => function (Individual $individual, $key, $individualId) {
                    $species = $individual->getSpecies();
                    $eventsForSpecies = $this->manager->getRepository(EventSpecies::class)
                        ->findBy(['species' => $species]);
                    $eventsForSpeciesIds = [];
                    foreach ($eventsForSpecies as $eventSpecies) {
                        $eventsForSpeciesIds[] = $eventSpecies->getEvent()->getId();
                    }

                    return [
                        'class' => 'individual-option individual-'.$individualId,
                        'selected' => 1 === count($this->individuals),
                        'data-species' => $species->getId(),
                        'data-available-events' => implode(',', $eventsForSpeciesIds),
                        'data-picture' => $species->getPicture(),
                    ];
                },
                'placeholder' => 'Choisir un individu',
            ])
            ->add('event', EntityType::class, [
                'class' => Event::class,
                'attr' => [
                    'class' => $selectEventClassAttr,
                    'required' => true,
                ],
                'choices' => $this->events,
                'choice_label' => function (Event $event, $key, $eventId) {
                    $choiceLabel = ucfirst($event->getName());
                    if (!empty($event->getStadeBbch())) {
                        $choiceLabel .= ' - Stade '.$event->getStadeBbch();
                    }

                    return $choiceLabel;
                },
                'choice_attr' => function (Event $event, $key, $eventId) {
                    $pictureSuffix = '';
                    if (!empty($event->getStadeBbch())) {
                        $pictureSuffix = '_'.substr($event->getStadeBbch(), 0, 1);
                    }

                    $eventsSpeciesForSpecies = $eventsForSpecies = $this->manager->getRepository(EventSpecies::class)
                        ->findBy(['species' => $this->individuals[0]->getSpecies()]);

                    return [
                        'class' => 'event-option event-'.$eventId,
                        'selected' => 1 === count($this->events),
                        'data-picture-suffix' => $pictureSuffix,
                        'data-description' => $event->getDescription(),
                        'hidden' => !in_array($event, $eventsSpeciesForSpecies),
                    ];
                },
                'placeholder' => 'Choisir un stade',
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('is_missing', CheckboxType::class, ['required' => false])
            ->add('details', TextareaType::class, ['required' => false])
            ->add('picture', FileType::class, [
                'required' => false,
                'label' => 'Votre photo',
                'attr' => [
                    'class' => 'upload-input',
                    'accept' => 'image/png, image/jpeg',
                ],
            ])
            ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Observation::class,
        ]);
        $resolver->setRequired('individuals');
        $resolver->setAllowedTypes('individuals', 'array');
    }
}
