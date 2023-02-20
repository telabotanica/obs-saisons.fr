<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StatsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
		$years = $options['years'];
		$builder
			->add('years', ChoiceType::class,
			['choices' => $options['years'],
				'attr' => [
					'onChange' => 'this.form.submit()',
					'class' => 'my-2 form-select text-center'
				]])
		;
	
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
			$year = $event->getData();
			$form = $event->getForm();
			// ... adding the name field if needed
			
		});
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
			'years' => null
            // Configure your form options here
        ]);
    }
}
