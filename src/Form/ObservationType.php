<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\EventSpecies;
use App\Entity\Individual;
use App\Entity\Observation;
use App\Entity\Station;
use App\Service\HandleDateTime;
use App\Service\UploadService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\File;

class ObservationType extends AbstractType
{
    private $manager;
    private $security;
    private $uploadFileService;
    private $isEdit;
    private $previousPicture;
    private $picture;
    private $individuals;
    private $eventSpeciesRepository;

    public function __construct(
        ManagerRegistry $manager,
        Security $security,
        UploadService $uploadFileService
    ) {
        $this->manager = $manager;
        $this->security = $security;
        $this->uploadFileService = $uploadFileService;
        $this->eventSpeciesRepository = $this->manager->getRepository(EventSpecies::class);
        $this->isEdit = false;
        $this->previousPicture = null;
        $this->picture = null;
        $this->individuals = [];
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // set individuals ($options is only accessible from formBuilder),
        if (empty($this->individuals) && $options['station']) {
            self::setIndividuals($options['station']);
        }

        $builder
            ->add('individual', EntityType::class, [
                'class' => Individual::class,
                'attr' => [
                    'class' => 'select-field',
                    'required' => true,
                ],
                'choices' => $this->individuals,
                'choice_label' => 'name',
                'choice_attr' => function (Individual $individual) {
                    $species = $individual->getSpecies();
                    $eventsSpeciesArray = $this->eventSpeciesRepository->findBy(['species' => $species]);

                    return [
                        'class' => 'individual-option individual-'.$individual->getId(),
                        'selected' => 1 === count($this->individuals),
                        'data-species' => $species->getId(),
                        'data-species-name' => $species->getVernacularName(),
                        'data-available-events' => implode(',', $this->getEventSpeciesIds($eventsSpeciesArray)),
                        'data-picture' => $species->getPicture(),
                        'data-aberrations-days' => json_encode($this->getAberrationDays($eventsSpeciesArray)),
                    ];
                },
                'placeholder' => 'Choisir un individu',
            ])
            ->add('event', EntityType::class, [
                'class' => Event::class,
                'attr' => [
                    'class' => 'select-field',
                    'required' => true,
                ],
                'choice_label' => function (Event $event) {
                    return $this->getEventChoiceLabel($event);
                },
                'choice_attr' => function (Event $event) {
                    return [
                        'class' => 'event-option event-'.$event->getId(),
                        'data-picture-suffix' => $this->getPictureSuffix($event),
                        'data-description' => $event->getDescription(),
                    ];
                },
                'placeholder' => 'Choisir un stade',
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    // On safari input type date renders input type text
                    // in this case french user provided date format is dd/mm/yyyy
                    'pattern' => '(^(((0[1-9]|1[0-9]|2[0-8])[\/](0[1-9]|1[012]))|((29|30|31)[\/](0[13578]|1[02]))|((29|30)[\/](0[4,6,9]|11)))[\/](19|[2-9][0-9])\d\d$)|(^29[\/]02[\/](19|[2-9][0-9])(00|04|08|12|16|20|24|28|32|36|40|44|48|52|56|60|64|68|72|76|80|84|88|92|96)$)',
                    'placeholder' => 'jj/mm/aaaa',
                ],
                'required' => true,
            ])
            ->add('isMissing', CheckboxType::class, ['required' => false])
            ->add('details', TextareaType::class, ['required' => false])
            ->add('picture', FileType::class, [
                'data_class' => null,
                'constraints' => [
                    new File([
                        'maxSize' => '5243k',
                        'mimeTypes' => [
                            'image/png',
                            'image/jpeg',
                        ],
                        'mimeTypesMessage' => 'Le format de l´image doit être .jpg ou .png',
                    ]),
                ],
                'required' => false,
                'label' => 'Votre photo',
                'attr' => [
                    'class' => 'upload-input',
                    'accept' => 'image/png, image/jpeg',
                ],
            ])
            // user picture removal request
            ->add('isDeletePicture', null, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('submit', SubmitType::class)
        ;

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            [$this, 'onPreSetData']
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            [$this, 'onPreSubmit']
        );

        $builder->addEventListener(
            FormEvents::SUBMIT,
            [$this, 'onSubmit']
        );
    }

    public function onPreSetData(FormEvent $formEvent)
    {
        $observation = $formEvent->getData();
        if ($observation && null !== $observation->getId()) {
            $this->isEdit = true;
            $this->previousPicture = $observation->getPicture();
            self::setIndividuals($observation->getIndividual()->getStation());
        }
    }

    public function onPreSubmit(FormEvent $formEvent)
    {
        $observation = $formEvent->getData();
        $isDeletePicture = $observation['isDeletePicture'] ?? false;

        $imageData = $observation['picture'] instanceof UploadedFile ? $observation['picture'] : null;
        $this->picture = $this->uploadFileService->setFile(
            $imageData,// input file data
            $this->previousPicture,
            $isDeletePicture// removal requested
        );
        // On safari input type date renders input type text
        // in this case french user provided date format is dd/mm/yyyy
        if (preg_match('/^([\d]{2}\/){2}[\d]{4}$/', $observation['date'])) {
            $frDateArray = array_reverse(explode('/', $observation['date']));
            $observation['date'] = implode('-', $frDateArray);
            // validation : see src/Entity/Observation.php date has Assert/Range Annotation
        }
        $observation['picture'] = $this->picture;
        $formEvent->setData($observation);
    }

    public function onSubmit(FormEvent $formEvent)
    {
        $observation = $formEvent->getData();
        $event = $this->manager->getRepository(Event::class)
            ->find($observation->getEvent())
        ;
        $individual = $this->manager->getRepository(Individual::class)
            ->find($observation->getIndividual())
        ;
        $observation->setEvent($event);
        $observation->setIndividual($individual);
        $observation->setDate($observation->getDate());
        $observation->setPicture($this->picture);
        $observation->setIsMissing(!empty($observation->getIsMissing()));
        if (!$this->isEdit) {
            $observation->setUser($this->security->getUser());
        }

        $formEvent->setData($observation);
    }

    private function setIndividuals(Station $station): self
    {
        $this->individuals = $this->manager->getRepository(Individual::class)
            ->findSpeciesIndividualsForStation($station)
        ;

        return $this;
    }

    private function getAberrationDays(array $eventsSpeciesArray): array
    {
        $aberrationDays = [];
        $transDateTime = new HandleDateTime();

        if (!empty($eventsSpeciesArray)) {
            foreach ($eventsSpeciesArray as $eventSpecies) {
                if ($eventSpecies) {
                    $eventId = $eventSpecies->getEvent()->getId();

                    if ($eventSpecies->getAberrationStartDay() && $eventSpecies->getAberrationEndDay()) {
                        $startDateTime = date_create_from_format('z', $eventSpecies->getAberrationStartDay());
                        $endDateTime = date_create_from_format('z', $eventSpecies->getAberrationEndDay());

                        $aberrationDays[] = [
                            'eventId' => $eventId,
                            'aberrationStartDay' => date_format($startDateTime, 'm-d'),
                            'aberrationEndDay' => date_format($endDateTime, 'm-d'),
                            'displayedStartDate' => $transDateTime->dateTransFormat('d MMMM', $startDateTime),
                            'displayedEndDate' => $transDateTime->dateTransFormat('d MMMM', $endDateTime),
                        ];
                    } else {
                        $aberrationDays[] = [
                            'eventId' => $eventId,
                            'aberrationStartDay' => null,
                            'aberrationEndDay' => null,
                            'displayedStartDate' => null,
                            'displayedEndDate' => null,
                        ];
                    }
                }
            }
        }

        return $aberrationDays;
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

        $resolver->setDefined('station');

        $resolver->setAllowedTypes('station', 'App\\Entity\\Station');
    }
}
