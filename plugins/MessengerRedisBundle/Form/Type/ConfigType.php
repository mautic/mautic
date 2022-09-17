<?php

namespace MauticPlugin\MessengerRedisBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ConfigType.
 */
class ConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $messengerConditions     = '{"config_messengerconfig_messenger_type":["async"]}';
        $messengerHideConditions = '{"config_messengerconfig_messenger_type":["sync"]}';

        $builder->add(
            'messenger_path',
            TextType::class,
            [
                'label'      => 'mautic.messenger.config.stream',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-show-on' => '{"config_messengerconfig_messenger_transport":["mautic.messenger.redis"]}',
                    'tooltip'      => 'mautic.messenger.config.stream.tooltip',
                    'onchange'     => 'Mautic.disableSendTestEmailButton()',
                ],
                'required'   => false,
            ]
        );

        $builder->add(
            'messenger_group',
            TextType::class,
            [
                'label'      => 'mautic.messenger.config.group',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-show-on' => '{"config_messengerconfig_messenger_transport":["mautic.messenger.redis"]}',
                    'tooltip'      => 'mautic.messenger.config.group.tooltip',
                    'onchange'     => 'Mautic.disableSendTestEmailButton()',
                ],
                'required'   => false,
            ]
        );

        $builder->add(
            'messenger_auto_setup',
            YesNoButtonGroupType::class,
            [
                'label'      => 'mautic.messenger.config.auto_setup',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'tooltip'      => 'mautic.messenger.config.auto_setup.tooltip',
                    'data-show-on' => '{"config_messengerconfig_messenger_transport":["mautic.messenger.redis"]}',
                ],
                'data'       => empty($options['data']['messenger_auto_setup']) ? 'false' : 'true',
                'required'   => false,
                'no_value'   => 'false',
                'yes_value'  => 'true',
            ]
        );

        $builder->add(
            'messenger_tls',
            YesNoButtonGroupType::class,
            [
                'label'      => 'mautic.messenger.config.tls',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'tooltip'      => 'mautic.messenger.config.tls.tooltip',
                    'data-show-on' => '{"config_messengerconfig_messenger_transport":["mautic.messenger.redis"]}',
                ],
                'data'       => empty($options['data']['messenger_tls']) ? 'false' : 'true',
                'required'   => false,
                'no_value'   => 'false',
                'yes_value'  => 'true',
            ]
        );
    }
}
