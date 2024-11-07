<?php

namespace App\Form;

use App\Entity\TypeRelays;
use App\Entity\User;
use App\Repository\TypeRelaysRepository;
use App\Service\UploadService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProfileType extends AbstractType
{
    private $uploadFileService;
    private $previousAvatar;
    private $avatar;

    public function __construct(
        UploadService $uploadFileService
    ) {
        $this->uploadFileService = $uploadFileService;
        $this->previousAvatar = null;
        $this->avatar = null;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('displayName', TextType::class, ['required' => false])
            ->add('name', TextType::class, ['required' => false])
            ->add('locality', TextType::class, ['required' => false])
            ->add('postCode', TextType::class, ['required' => false])
            ->add('country', TextType::class, ['required' => false])
            ->add('profileType', ChoiceType::class, [
                'choices' => [
                    'Particulier' => 'Particulier',
                    'Établissement scolaire' => 'Établissement scolaire',
                    'Association' => 'Association',
                    'Professionnel' => 'Professionnel',
                    'Autre' => 'Autre',
                ],
                'required' => false,
            ])
            ->add('isNewsletterSubscriber', CheckboxType::class, ['required' => false])
            ->add('avatar', FileType::class, [
                'data_class' => null,
                'constraints' => [
                    new File([
                        'maxSize' => '5243k',
                        'mimeTypes' => [
                            'image/png',
                            'image/jpeg',
                        ],
                        'mimeTypesMessage' => 'Le format de l’image doit être .jpg ou .png',
                    ]),
                ],
                'required' => false,
                'label' => 'Votre photo de profil',
                'attr' => [
                    'class' => 'upload-input',
                    'accept' => 'image/png, image/jpeg',
                ],
            ])
			->add('roles', ChoiceType::class, [
				'label'=>'Role',
				'choices' => [
					'Droits' => [
						'Admin' => 'ROLE_ADMIN',
                        'Relais'=> 'ROLE_RELAY',
						'Utilisateur' => 'ROLE_USER'
					],
				],
				'attr'=> ['class'=> 'form-control bg-light'],
				'multiple' => true,
			])
            ->add('typeRelays', EntityType::class, array(
                'class' => TypeReLays::class,
                'choice_label' => function(TypeRelays $type) {
                    return sprintf('(%d) %s (%s)', $type->getId(), $type->getName(),$type->getCode());
                },
                'query_builder' => function (TypeRelaysRepository $er) {
                     return $er->createQueryBuilder('t');
                },
                'required'=>false,
                'empty_data' => null,
                'attr'=> ['class'=> 'form-control bg-light']
            ))
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
        $user = $formEvent->getData();
        if ($user && null !== $user->getId()) {
            $this->previousAvatar = $user->getAvatar();
        }
    }

    public function onPreSubmit(FormEvent $formEvent)
    {
        $user = $formEvent->getData();
        $isDeletePicture = $user['isDeletePicture'] ?? false;

        $imageData = $user['avatar'] instanceof UploadedFile ? $user['avatar'] : null;
        $this->avatar = $this->uploadFileService->setFile(
            $imageData,// input file data
            $this->previousAvatar,
            $isDeletePicture// removal requested
        );
        $user['avatar'] = $this->avatar;
        $formEvent->setData($user);
    }

    public function onSubmit(FormEvent $formEvent)
    {
        $user = $formEvent->getData();

        $user->setAvatar($this->avatar);
        $user->setIsNewsletterSubscriber(!empty($user->getIsNewsletterSubscriber()));

        $formEvent->setData($user);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
