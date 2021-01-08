<?php

namespace App\Form;

use App\Entity\Post;
use App\Service\HandleDateTime;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class EventPostType extends AbstractType implements EventSubscriberInterface
{
    private Security $security;
    private bool $isEdit;
    private HandleDateTime $handleDateTime;

    public function __construct(
        Security $security,
        HandleDateTime $handleDateTime
    ) {
        $this->handleDateTime = $handleDateTime;
        $this->security = $security;
        $this->isEdit = false;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('post_fields', PostType::class, [
                'data_class' => Post::class,
            ])
            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
                'constraints' => [
                    new LessThanOrEqual([
                        'propertyPath' => 'parent.all[endDate].data',
                    ]),
                ],
                'attr' => [
                    // On safari input type date renders input type text
                    // in this case french user provided date format is dd/mm/yyyy
                    'pattern' => '(^(((0[1-9]|1[0-9]|2[0-8])[\/](0[1-9]|1[012]))|((29|30|31)[\/](0[13578]|1[02]))|((29|30)[\/](0[4,6,9]|11)))[\/](19|[2-9][0-9])\d\d$)|(^29[\/]02[\/](19|[2-9][0-9])(00|04|08|12|16|20|24|28|32|36|40|44|48|52|56|60|64|68|72|76|80|84|88|92|96)$)',
                    'placeholder' => 'jj/mm/aaaa',
                ],
                'required' => true,
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
                'constraints' => [
                    new GreaterThanOrEqual([
                        'propertyPath' => 'parent.all[endDate].data',
                    ]),
                ],
                'attr' => [
                    // On safari input type date renders input type text
                    // in this case french user provided date format is dd/mm/yyyy
                    'pattern' => '(^(((0[1-9]|1[0-9]|2[0-8])[\/](0[1-9]|1[012]))|((29|30|31)[\/](0[13578]|1[02]))|((29|30)[\/](0[4,6,9]|11)))[\/](19|[2-9][0-9])\d\d$)|(^29[\/]02[\/](19|[2-9][0-9])(00|04|08|12|16|20|24|28|32|36|40|44|48|52|56|60|64|68|72|76|80|84|88|92|96)$)',
                    'placeholder' => 'jj/mm/aaaa',
                ],
                'required' => false,
            ])
            ->add('location', TextType::class, ['required' => true])
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
        $this->isEdit = $post && null !== $post->getId();
    }

    public function onPreSubmit(FormEvent $formEvent)
    {
        $post = $formEvent->getData();
        $post['startDate'] = $this->handleDateTime->browserSupportDate($post['startDate']);
        $post['endDate'] = $this->handleDateTime->browserSupportDate($post['endDate']);

        $formEvent->setData($post);
    }

    public function onSubmit(FormEvent $formEvent)
    {
        $post = $formEvent->getData();

        if (!$this->isEdit) {
            $post->setAuthor($this->security->getUser());
            $post->setStatus(Post::STATUS_PENDING);
        }

        $formEvent->setData($post);
    }

    /**
     * Valid if post is one day event and date is not in the past, or is more than one day event.
     */
    public function validateOneDayEventDate(Post $post, ExecutionContextInterface $context): void
    {
        $isOneDayEvent = $post->getEndDate() === $post->getStartDate() || empty($post->getEndDate());

        if ($isOneDayEvent && $post->getStartDate() < new \DateTime('now')) {
            $context->buildViolation('La date d’un évènement pontuel ne peut se situer dans le passé')
                ->atPath('startDate')
                ->addViolation();
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
            'constraints' => [
                new Callback([$this, 'validateOneDayEventDate']),
            ],
            'allow_extra_fields' => true,
        ]);
    }
}
