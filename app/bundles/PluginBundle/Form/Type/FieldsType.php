<?php

namespace Mautic\PluginBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FieldsType extends AbstractType
{
    use FieldsTypeTrait;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->buildFormFields($builder, $options, $options['integration_fields'], $options['mautic_fields'], '', $options['limit'], $options['start']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $this->configureFieldOptions($resolver, 'lead');
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'integration_fields';
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $this->buildFieldView($view, $options);
    }
}
