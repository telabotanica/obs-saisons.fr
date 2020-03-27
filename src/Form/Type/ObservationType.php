<?php

namespace App\Form\Type;

use App\DisplayData\Station\StationDisplayData;
use App\Entity\Event;
use App\Entity\Individual;
use App\Entity\Observation;
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
    /**
     * @var StationDisplayData
     */
    private $stationDisplayData;
    private $individuals;
    private $events;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->stationDisplayData = $options['station_display_data'];

        $this->individuals = $this->stationDisplayData->stationAllSpeciesIndividuals;
        $selectIndividualClassAttr = 'select-field';
        if (1 === count($this->individuals)) {
            $selectIndividualClassAttr .= ' disabled';
        }

        $this->events = $this->stationDisplayData->getStationAllEvents();
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

                    return [
                        'class' => 'individual-option individual-'.$individualId,
                        'selected' => 1 === count($this->individuals),
                        'data-species' => $species->getId(),
                        'data-available-events' => implode(',', $this->stationDisplayData->getEventIdsForSpecies($species)),
                        'data-event-pictures' => json_encode($this->stationDisplayData->getEventSpeciesPictures($species)),
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
                    return [
                        'class' => 'event-option event-'.$eventId,
                        'selected' => 1 === count($this->events),
                        'data-description' => $event->getDescription(),
                        'hidden' => !in_array($event, $this->stationDisplayData->getEventsForSpecies($this->individuals[0]->getSpecies())),
                    ];
                },
                'placeholder' => 'Choisir un stade',
            ])
            ->add('obs_date', DateType::class, [
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('is_missing', CheckboxType::class, ['required' => false])
            ->add('details', TextareaType::class, ['required' => false])
            //->add('picture', FileType::class)
            ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Observation::class,
        ]);
        $resolver->setRequired('station_display_data');
        $resolver->setAllowedTypes('station_display_data', 'App\DisplayData\Station\StationDisplayData');
    }
}
