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
    private $manager;
    private $individuals;
    private $eventSpeciesRepository;
    private $events;
    private $firstSpecies;
    private $firstSpeciesEventSpecies;

    public function __construct(ManagerRegistry $manager)
    {
        $this->manager = $manager;
        $this->eventSpeciesRepository = $this->manager->getRepository(EventSpecies::class);
        $this->events = [];
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // set individuals ($options is only accessible from formBuilder),
        //events, first species and first EventSpecies
        self::setProperties($options);

        $builder
            ->add('individual', EntityType::class, [
                'class' => Individual::class,
                'attr' => [
                    'class' => $this->getSelectClassAttrs($this->individuals),
                    'required' => true,
                ],
                'choices' => $this->individuals,
                'choice_label' => 'name',
                'choice_attr' => function (Individual $individual, $key, $individualId) {
                    $species = $individual->getSpecies();
                    $eventsForSpeciesIds = $this->getEventSpeciesIds(
                        $this->eventSpeciesRepository->findBy(['species' => $species])
                    );

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
                    'class' => $this->getSelectClassAttrs($this->events),
                    'required' => true,
                ],
                'choices' => $this->events,
                'choice_label' => function (Event $event) {
                    return $this->getEventChoiceLabel($event);
                },
                'choice_attr' => function (Event $event, $key, $eventId) {
                    $aberrationDays = $this->getAberrationDays($event);

                    return [
                        'class' => 'event-option event-'.$eventId,
                        'selected' => 1 === count($this->events),
                        'data-picture-suffix' => $this->getPictureSuffix($event),
                        'data-description' => $event->getDescription(),
                        'data-aberration-start-day' => $aberrationDays['start'],
                        'data-aberration-end-day' => $aberrationDays['end'],
                        'hidden' => !in_array($event, $this->firstSpeciesEventSpecies),
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

    private function setProperties($options): self
    {
        // set individuals
        $this->individuals = $options['individuals'];
        // set events
        $allSpecies = [];
        foreach ($this->individuals as $individual) {
            $species = $individual->getSpecies();
            if (!in_array($species, $allSpecies)) {
                $allSpecies[] = $species;
                $eventSpeciesForSpecies = $this->eventSpeciesRepository->findBy(['species' => $species])
                ;
                foreach ($eventSpeciesForSpecies as $eventSpecies) {
                    $event = $eventSpecies->getEvent();
                    if (!in_array($event, $this->events)) {
                        $this->events[] = $event;
                    }
                }
            }
        }
        // set first species
        $this->firstSpecies = $this->individuals[0]->getSpecies();
        // set first eventSpecies
        $this->firstSpeciesEventSpecies = $this->eventSpeciesRepository->findBy(['species' => $this->firstSpecies]);

        return $this;
    }

    private function getSelectClassAttrs(array $array): string
    {
        $selectClassAttrs = 'select-field';
        if (1 === count($array)) {
            $selectClassAttrs .= ' disabled';
        }

        return $selectClassAttrs;
    }

    private function getAberrationDays(Event $event): array
    {
        $eventsSpeciesArray = $this->eventSpeciesRepository->findBy(['species' => $this->firstSpecies, 'event' => $event]);
        $start = null;
        $end = null;
        if (!empty($eventsSpeciesArray) && $eventsSpeciesArray[0] && $eventsSpeciesArray[0]->getAberrationStartDay() && $eventsSpeciesArray[0]->getAberrationEndDay()) {
            $start = date_format(date_create_from_format('z', $eventsSpeciesArray[0]->getAberrationStartDay()), 'm-d');
            $end = date_format(date_create_from_format('z', $eventsSpeciesArray[0]->getAberrationEndDay()), 'm-d');
        }

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    private function getEventSpeciesIds(array $eventsForSpecies): array
    {
        $eventsForSpeciesIds = [];
        foreach ($eventsForSpecies as $eventSpecies) {
            $eventsForSpeciesIds[] = $eventSpecies->getEvent()->getId();
        }

        return $eventsForSpeciesIds;
    }

    private function getEventChoiceLabel(Event $event): string
    {
        $choiceLabel = ucfirst($event->getName());
        if (!empty($event->getStadeBbch())) {
            $choiceLabel .= ' - Stade '.$event->getStadeBbch();
        }

        return $choiceLabel;
    }

    private function getPictureSuffix(Event $event): string
    {
        $stadeBbch = $event->getStadeBbch();
        return $stadeBbch ? '_'.substr($stadeBbch, 0, 1) : '';
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
