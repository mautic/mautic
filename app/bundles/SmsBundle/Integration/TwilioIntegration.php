<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Integration;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Class TwilioIntegration.
 */
class TwilioIntegration extends AbstractIntegration
{
    protected bool $coreIntegration = true;

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Twilio';
    }

    public function getIcon()
    {
        return 'app/bundles/SmsBundle/Assets/img/Twilio.png';
    }

    public function getSecretKeys()
    {
        return ['password'];
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'username' => 'mautic.sms.config.form.sms.username',
            'password' => 'mautic.sms.config.form.sms.password',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'none';
    }

    /**
     * @param \Mautic\PluginBundle\Integration\Form|FormBuilder $builder
     * @param array                                             $data
     * @param string                                            $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ('features' == $formArea) {
            $builder->add(
                'sending_phone_number',
                TextType::class,
                [
                    'label'      => 'mautic.sms.config.form.sms.sending_phone_number',
                    'label_attr' => ['class' => 'control-label'],
                    'required'   => false,
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.sms.config.form.sms.sending_phone_number.tooltip',
                    ],
                ]
            );
            $builder->add(
                'disable_trackable_urls',
                YesNoButtonGroupType::class,
                [
                    'label' => 'mautic.sms.config.form.sms.disable_trackable_urls',
                    'attr'  => [
                        'tooltip' => 'mautic.sms.config.form.sms.disable_trackable_urls.tooltip',
                    ],
                    'data'=> !empty($data['disable_trackable_urls']) ? true : false,
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
