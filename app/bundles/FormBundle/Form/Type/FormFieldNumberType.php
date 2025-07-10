<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<FormFieldNumberType>
 */
class FormFieldNumberType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('placeholder', TextType::class, [
            'label'      => 'mautic.form.field.form.property_placeholder',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'required'   => false,
        ]);

        $builder->add(
            'precision',
            IntegerType::class,
            [
                'label'      => 'mautic.form.field.form.number_precision',
                'label_attr' => ['class' => 'control-label'],
                'data'       => $options['data']['precision'] ?? 0,
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.form.field.form.number_precision.tooltip',
                ],
                'required'   => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'formfield_number';
    }
}
