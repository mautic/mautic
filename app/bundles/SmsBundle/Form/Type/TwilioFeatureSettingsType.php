<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<array<mixed>>
 */
final class TwilioFeatureSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'messaging_service_sid',
            TextType::class,
            [
                'label'      => 'mautic.sms.config.form.sms.messaging_service_sid',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.sms.config.form.sms.messaging_service_sid.tooltip',
                ],
            ]
        );

        $builder->add(
            'frequency_number',
            NumberType::class,
            [
                'scale'      => 0,
                'label'      => 'mautic.sms.list.frequency.number',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class' => 'form-control frequency',
                ],
            ]
        );

        $builder->add(
            'frequency_time',
            ChoiceType::class,
            [
                'choices' => [
                    'day'   => 'DAY',
                    'week'  => 'WEEK',
                    'month' => 'MONTH',
                ],
                'label'      => 'mautic.lead.list.frequency.times',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'multiple'   => false,
                'attr'       => [
                    'class' => 'form-control frequency',
                ],
            ]
        );
    }
}
