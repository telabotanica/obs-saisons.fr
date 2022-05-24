<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

class ContactType extends AbstractType
{
    public const HONEYPOT_FIELD_NAME = 'email';
    public const EMAIL_FIELD_NAME = 'information';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(self::EMAIL_FIELD_NAME, EmailType::class, ['required' => true])
            ->add(self::HONEYPOT_FIELD_NAME, TextType::class, ['required' => false])
            ->add('subject', TextType::class, ['required' => false])
            ->add('message', TextareaType::class, ['required' => true])
            ->add('submit', SubmitType::class)
            ->setMethod(Request::METHOD_POST);
    }
}
