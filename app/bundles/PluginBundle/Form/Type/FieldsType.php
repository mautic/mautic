<?php

namespace Mautic\PluginBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<array<mixed>>
 */
class FieldsType extends AbstractType
{
    use FieldsTypeTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->buildFormFields($builder, $options, $options['integration_fields'], $options['mautic_fields'], '', $options['limit'], $options['start']);
    }

    public function configureOptions(OptionsResolver $resolver): void
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

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $this->buildFieldView($view, $options);
    }
}
