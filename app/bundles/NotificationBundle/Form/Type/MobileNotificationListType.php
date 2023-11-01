<?php

namespace Mautic\NotificationBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\EntityLookupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class MobileNotificationListType.
 */
class MobileNotificationListType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'modal_route'         => 'mautic_mobile_notification_action',
                'modal_header'        => 'mautic.notification.mobile.header.new',
                'model'               => 'notification',
                'model_lookup_method' => 'getLookupResults',
                'lookup_arguments'    => function (Options $options) {
                    return [
                        'type'    => 'mobile_notification',
                        'filter'  => '$data',
                        'limit'   => 0,
                        'start'   => 0,
                        'options' => [
                            'notification_type' => $options['notification_type'],
                        ],
                    ];
                },
                'ajax_lookup_action' => function (Options $options) {
                    $query = [
                        'notification_type' => $options['notification_type'],
                    ];

                    return 'notification:getLookupChoiceList&'.http_build_query($query);
                },
                'expanded'          => false,
                'multiple'          => true,
                'required'          => false,
                'notification_type' => 'template',
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'mobilenotification_list';
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return EntityLookupType::class;
    }
}
