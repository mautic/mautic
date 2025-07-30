<?php

namespace Mautic\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<mixed>
 */
class FormFieldBooleanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('yes', TextType::class, [
            'label'      => 'mautic.form.field.boolean.yes_label',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'data'       => $options['data']['yes'] ?? 'mautic.core.form.yes',
            'required'   => false,
        ]);

        $builder->add('no', TextType::class, [
            'label'      => 'mautic.form.field.boolean.no_label',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'data'       => $options['data']['no'] ?? 'mautic.core.form.no',
            'required'   => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'formfield_boolean';
    }
}
