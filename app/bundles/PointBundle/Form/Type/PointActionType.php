<?php

namespace Mautic\PointBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<array<mixed>>
 */
class PointActionType extends AbstractType
{
    /**
     * @param FormBuilderInterface<array<mixed>|null> $builder
     * @param array<string, mixed>                    $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $masks           = [];
        $formTypeOptions = [
            'label' => false,
        ];
        if (!empty($options['formTypeOptions'])) {
            $formTypeOptions = array_merge($formTypeOptions, $options['formTypeOptions']);
        }

        if (isset($options['formType'])) {
            $builder->add('properties', $options['formType'], $formTypeOptions);
        }

        if (isset($options['settings']['formTypeCleanMasks'])) {
            $masks['properties'] = $options['settings']['formTypeCleanMasks'];
        }

        $builder->addEventSubscriber(new CleanFormSubscriber($masks));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'formType'        => null,
            'formTypeOptions' => [],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'pointaction';
    }
}
