<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<mixed>
 */
final class FormFieldBooleanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('yes', TextType::class, [
            'label'      => 'mautic.form.field.boolean.yes_label',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'data'       => $options['data']['yes'] ?? '',
            'required'   => false,
        ]);

        $builder->add('no', TextType::class, [
            'label'      => 'mautic.form.field.boolean.no_label',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'data'       => $options['data']['no'] ?? '',
            'required'   => false,
        ]);
    }
}
