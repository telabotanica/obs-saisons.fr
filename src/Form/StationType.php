<?php

namespace App\Form;

use App\Entity\Station;
use App\Service\UploadService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\File;

class StationType extends AbstractType
{
    private $security;
    private $uploadFileService;
    private $isEdit;
    private $previousHeaderImage;
    private $headerImage;

    public function __construct(
        Security $security,
        UploadService $uploadFileService
    ) {
        $this->security = $security;
        $this->uploadFileService = $uploadFileService;
        $this->isEdit = false;
        $this->previousHeaderImage = null;
        $this->headerImage = null;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, ['required' => true])
            ->add('description', TextareaType::class, ['required' => false])
            ->add('latitude', NumberType::class, [
                'required' => true,
                'html5' => true,
            ])
            ->add('longitude', NumberType::class, [
                'required' => true,
                'html5' => true,
            ])
            ->add('locality', TextType::class, ['required' => true])
            ->add('inseeCode', TextType::class, [
                'required' => true,
            ])
            ->add('altitude', HiddenType::class)
            ->add('habitat', ChoiceType::class, [
                'choices' => $this->getHabitats(),
                'required' => true,
            ])
            ->add('isPrivate', CheckboxType::class, ['required' => false])
            ->add('headerImage', FileType::class, [
                'mapped' => false,
                'data_class' => null,
                'constraints' => [
                    new File([
                        'maxSize' => '5243k',
                        'maxSizeMessage'=> 'Votre fichier est trop lourd ! (5Mo maximum)',
                        'mimeTypes' => [
                            'image/png',
                            'image/jpeg',
                        ],
                        'mimeTypesMessage' => 'Le format de l´image doit être .jpg ou .png',
                    ]),
                ],
                'required' => false,
                'label' => 'Image de la station',
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
        $station = $formEvent->getData();
        if ($station && null !== $station->getId()) {
            $this->isEdit = true;
            $this->previousHeaderImage = $station->getHeaderImage();
        }
    }

    public function onPreSubmit(FormEvent $formEvent)
    {
        $station = $formEvent->getData();
        $isDeletePicture = $station['isDeletePicture'] ?? false;

        /*
        $imageData = $station['headerImage'] instanceof UploadedFile ? $station['headerImage'] : null;
        $this->headerImage = $this->uploadFileService->setFile(
            $imageData,// input file data
            $this->previousHeaderImage,
            $isDeletePicture// removal requested
        );
        $station['headerImage'] = $this->headerImage;
        */
        $formEvent->setData($station);
    }

    public function onSubmit(FormEvent $formEvent)
    {
        $station = $formEvent->getData();

        $station->setHeaderImage($this->headerImage);
        $station->setIsPrivate(!empty($station->getIsPrivate()));
        if (!$this->isEdit) {
            $station->setUser($this->security->getUser());
        }

        $formEvent->setData($station);
    }

    private function getHabitats(): array
    {
        $choices = [];
        foreach (Station::HABITATS as $habitat) {
            $choices[ucfirst($habitat)] = $habitat;
        }

        return $choices;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Station::class,
        ]);
    }
}
