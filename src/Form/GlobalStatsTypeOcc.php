<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GlobalStatsTypeOcc extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
		$departmentsOcc = $options['departmentsOcc'];
        $departmentsPaca = $options['departmentsPaca'];
		$builder
			->add('departmentsOcc', ChoiceType::class,
			['choices' => $departmentsOcc,
				'attr' => [
					'onChange' => 'this.form.submit()',
					'class' => 'my-2 form-select text-center'
				]]);
          
	
		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
		
            $departmentPaca = $event->getData();
			$form = $event->getForm();
			// ... adding the name field if needed
			
		});
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
			'departmentsOcc' => null,
            'departmentsPaca' => null
        ]);
    }
}
