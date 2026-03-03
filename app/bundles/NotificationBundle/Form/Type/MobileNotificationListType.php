<?php

namespace Mautic\NotificationBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\EntityLookupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<array<mixed>>
 */
class MobileNotificationListType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'modal_route'         => 'mautic_mobile_notification_action',
                'modal_header'        => 'mautic.notification.mobile.header.new',
                'model'               => 'notification',
                'model_lookup_method' => 'getLookupResults',
                'lookup_arguments'    => fn (Options $options): array => [
                    'type'    => 'mobile_notification',
                    'filter'  => '$data',
                    'limit'   => 0,
                    'start'   => 0,
                    'options' => [
                        'notification_type' => $options['notification_type'],
                        'top_level'         => $options['top_level'],
                        'ignore_ids'        => $options['ignore_ids'],
                    ],
                ],
                'ajax_lookup_action' => function (Options $options): string {
                    $query = [
                        'notification_type' => $options['notification_type'],
                        'top_level'         => $options['top_level'],
                        'ignore_ids'        => $options['ignore_ids'],
                    ];

                    return 'notification:getLookupChoiceList&'.http_build_query($query);
                },
                'expanded'          => false,
                'multiple'          => true,
                'required'          => false,
                'notification_type' => 'template',
                'top_level'         => 'translation',
                'ignore_ids'        => [],
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'mobilenotification_list';
    }

    public function getParent(): ?string
    {
        return EntityLookupType::class;
    }
}
