<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<mixed>
 */
final class FormFieldCheckboxGroupType extends AbstractType
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'minimum',
            IntegerType::class,
            [
                'label'      => 'mautic.form.field.checkboxgrp.minimum',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                    'min'   => 0,
                ],
                'required'    => false,
                'constraints' => [
                    new Assert\PositiveOrZero(),
                ],
            ]
        );

        $builder->add(
            'min_message',
            TextType::class,
            [
                'label'      => 'mautic.form.field.checkboxgrp.min_message',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'         => 'form-control',
                    'placeholder'   => $this->translator->trans('mautic.form.field.checkboxgrp.min_message.placeholder'),
                    'tooltip'       => 'mautic.form.field.checkboxgrp.min_message.tooltip',
                ],
                'required'   => false,
                'data'       => $options['data']['min_message'] ?? null,
            ]
        );

        $builder->add(
            'maximum',
            IntegerType::class,
            [
                'label'      => 'mautic.form.field.checkboxgrp.maximum',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                    'min'   => 0,
                ],
                'required'    => false,
                'constraints' => [
                    new Assert\PositiveOrZero(),
                ],
            ]
        );

        $builder->add(
            'max_message',
            TextType::class,
            [
                'label'      => 'mautic.form.field.checkboxgrp.max_message',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'         => 'form-control',
                    'placeholder'   => $this->translator->trans('mautic.form.field.checkboxgrp.max_message.placeholder'),
                    'tooltip'       => 'mautic.form.field.checkboxgrp.max_message.tooltip',
                ],
                'required'   => false,
                'data'       => $options['data']['max_message'] ?? null,
            ]
        );

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            $form = $event->getForm();

            if (isset($data['minimum'], $data['maximum']) && '' !== $data['minimum'] && '' !== $data['maximum']) {
                if ((int) $data['maximum'] < (int) $data['minimum']) {
                    $form->get('maximum')->addError(new FormError(
                        $this->translator->trans('mautic.form.field.checkboxgrp.range.invalid', [], 'validators')
                    ));
                }
            }
        });
    }
}
