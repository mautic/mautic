<?php

namespace Mautic\SmsBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class TwilioIntegration extends AbstractIntegration
{
    protected bool $coreIntegration = true;

    public function getName(): string
    {
        return 'Twilio';
    }

    public function getIcon(): string
    {
        return 'app/bundles/SmsBundle/Assets/img/Twilio.png';
    }

    public function getSecretKeys(): array
    {
        return ['password'];
    }

    /**
     * @return array<string, string>
     */
    public function getRequiredKeyFields(): array
    {
        return [
            'username' => 'mautic.sms.config.form.sms.username',
            'password' => 'mautic.sms.config.form.sms.password',
        ];
    }

    public function getAuthenticationType(): string
    {
        return 'none';
    }

    /**
     * @param \Mautic\PluginBundle\Integration\Form|FormBuilder $builder
     * @param array                                             $data
     * @param string                                            $formArea
     */
    public function appendToForm(&$builder, $data, $formArea): void
    {
        if ('features' == $formArea) {
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
            $builder->add('frequency_number', NumberType::class,
                [
                    'scale'      => 0,
                    'label'      => 'mautic.sms.list.frequency.number',
                    'label_attr' => ['class' => 'control-label'],
                    'required'   => false,
                    'attr'       => [
                        'class' => 'form-control frequency',
                    ],
                ]);
            $builder->add('frequency_time', ChoiceType::class,
                [
                    'choices' => [
                        'day'   => 'DAY',
                        'week'  => 'WEEK',
                        'month' => 'MONTH',
                    ],
                    'label'             => 'mautic.lead.list.frequency.times',
                    'label_attr'        => ['class' => 'control-label'],
                    'required'          => false,
                    'multiple'          => false,
                    'attr'              => [
                        'class' => 'form-control frequency',
                    ],
                ]);
        }
    }
}
