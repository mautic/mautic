<?php

namespace Mautic\PageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<array<mixed>>
 */
class AbTestPropertiesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $options = ['label' => false];
        if (isset($options['formTypeOptions'])) {
            $options = array_merge($options, $options['formTypeOptions']);
        }
        $builder->add('properties', $options['formType'], $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined([
            'formType',
            'formTypeOptions',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'page_abtest_settings';
    }
}
