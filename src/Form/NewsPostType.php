<?php

namespace App\Form;

use App\Entity\Post;
use App\Service\UploadService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\File;

class NewsPostType extends AbstractType implements EventSubscriberInterface
{
    private Security $security;
    private UploadService $uploadFileService;
    private bool $isEdit;
    private $previousCover;
    private $cover;

    public function __construct(
        Security $security,
        UploadService $uploadFileService
    ) {
        $this->security = $security;
        $this->uploadFileService = $uploadFileService;
        $this->isEdit = false;
        $this->previousCover = null;
        $this->cover = null;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('post_fields', PostType::class, [
                'data_class' => Post::class,
            ])
            ->add('cover', FileType::class, [
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
                'label' => 'Image de couverture',
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

        $builder->addEventSubscriber($this);
    }

    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'onPreSetData',
            FormEvents::PRE_SUBMIT => 'onPreSubmit',
            FormEvents::SUBMIT => 'onSubmit',
        ];
    }

    public function onPreSetData(FormEvent $formEvent)
    {
        $post = $formEvent->getData();
        if ($post && null !== $post->getId()) {
            $this->isEdit = true;
            $this->previousCover = $post->getCover();
        }
    }

    public function onPreSubmit(FormEvent $formEvent)
    {
        $post = $formEvent->getData();
        $isDeletePicture = $post['isDeletePicture'] ?? false;

        $imageData = $post['cover'] instanceof UploadedFile ? $post['cover'] : null;
        $this->cover = $this->uploadFileService->setFile(
            $imageData,// input file data
            $this->previousCover,
            $isDeletePicture// removal requested
        );
        $post['cover'] = $this->cover;
        $formEvent->setData($post);
    }

    public function onSubmit(FormEvent $formEvent)
    {
        $post = $formEvent->getData();

        $post->setCover($this->cover);
        if (!$this->isEdit) {
            $post->setAuthor($this->security->getUser());
            $post->setStatus(Post::STATUS_PENDING);
        }

        $formEvent->setData($post);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
            'allow_extra_fields' => true,
        ]);
    }
}
