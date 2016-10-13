<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ConfigType.
 */
class ConfigType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'sms_enabled',
            'yesno_button_group',
            [
                'label' => 'mautic.sms.config.form.sms.enabled',
                'data'  => (bool) $options['data']['sms_enabled'],
                'attr'  => [
                    'tooltip' => 'mautic.sms.config.form.sms.enabled.tooltip',
                ],
            ]
        );

        $formModifier = function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            // Add required restraints if sms is enabled
            $constraints = (empty($data['sms_enabled'])) ?
                [] :
                [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ];

            $form->add(
                'sms_username',
                'text',
                [
                    'label' => 'mautic.sms.config.form.sms.username',
                    'attr'  => [
                        'tooltip'      => 'mautic.sms.config.form.sms.username.tooltip',
                        'class'        => 'form-control',
                        'data-show-on' => '{"config_smsconfig_sms_enabled_1":"checked"}',
                    ],
                    'constraints' => $constraints,
                ]
            );

            $form->add(
                'sms_password',
                'text',
                [
                    'label' => 'mautic.sms.config.form.sms.password',
                    'attr'  => [
                        'tooltip'      => 'mautic.sms.config.form.sms.password.tooltip',
                        'class'        => 'form-control',
                        'data-show-on' => '{"config_smsconfig_sms_enabled_1":"checked"}',
                    ],
                    'constraints' => $constraints,
                ]
            );

            $form->add(
                'sms_sending_phone_number',
                'text',
                [
                    'label' => 'mautic.sms.config.form.sms.sending_phone_number',
                    'attr'  => [
                        'tooltip'      => 'mautic.sms.config.form.sms.sending_phone_number.tooltip',
                        'class'        => 'form-control',
                        'data-show-on' => '{"config_smsconfig_sms_enabled_1":"checked"}',
                    ],
                    'constraints' => $constraints,
                ]
            );
        };
        $builder->add('sms_frequency_number', 'number',
            [
                'precision'  => 0,
                'label'      => 'mautic.sms.list.frequency.number',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class' => 'form-control frequency',
                ],
            ]);
        $builder->add('sms_frequency_time', 'choice',
            [
                'choices' => [
                    'DAY'   => 'day',
                    'WEEK'  => 'week',
                    'MONTH' => 'month',
                ],
                'label'      => 'mautic.lead.list.frequency.times',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'multiple'   => false,
                'attr'       => [
                    'class' => 'form-control frequency',
                ],
            ]);

        // Before submit
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            $formModifier
        );

        // After submit
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            $formModifier
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'smsconfig';
    }
}
