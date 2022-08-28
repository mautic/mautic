<?php

namespace Mautic\PageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class AbTestPropertiesType.
 */
class AbTestPropertiesType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $options = ['label' => false];
        if (isset($options['formTypeOptions'])) {
            $options = array_merge($options, $options['formTypeOptions']);
        }
        $builder->add('properties', $options['formType'], $options);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined([
            'formType',
            'formTypeOptions',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'page_abtest_settings';
    }
}
