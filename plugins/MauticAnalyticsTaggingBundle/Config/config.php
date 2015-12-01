<?php

return array(
    'name' => 'Google Analytics tagging',
    'description' => 'Tagging for emails.',
    'version' => '1.0',
    'author' => 'kuzmany.biz',
    'services' => array(
        'forms' => array(
            'mautic.form.type.tagging' => array(
                'class' => 'MauticPlugin\MauticAnalyticsTaggingBundle\Form\Type\ConfigType',
                'alias' => 'tagging'
            ),
        ),
        'events' => array(
            'mautic.analyticstagging.emailbundle.subscriber' => array(
                'class' => 'MauticPlugin\MauticAnalyticsTaggingBundle\EventListener\EmailSubscriber'
            ),
            'mautic.analyticstagging.configbundle.subscriber' => array(
                'class' => 'MauticPlugin\MauticAnalyticsTaggingBundle\EventListener\ConfigSubscriber'
            ),
        ),
    ),
    'parameters' => array(
        'utm_source' => 'mautic',
        'utm_medium' => 'email',
        'utm_campaign' => 'subject',
        'remove_accents' => true,
    )
);
