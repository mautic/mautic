<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ConfigType.
 */
class ConfigTrackingPageType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'track_contact_by_ip',
            'yesno_button_group',
            [
                'label' => 'mautic.page.config.form.track_contact_by_ip',
                'data'  => (bool) $options['data']['track_contact_by_ip'],
                'attr'  => [
                    'tooltip' => 'mautic.page.config.form.track_contact_by_ip.tooltip',
                ],
            ]
        );

        $builder->add('track_by_tracking_url', 'yesno_button_group', [
            'label' => 'mautic.page.config.form.track.by.tracking.url',
            'data'  => (bool) $options['data']['track_by_tracking_url'],
            'attr'  => [
                'tooltip' => 'mautic.page.config.form.track.by.tracking.url.tooltip',
            ],
        ]);

        $builder->add('track_by_fingerprint', 'yesno_button_group', [
            'label' => 'mautic.page.config.form.track.by.fingerprint',
            'data'  => (bool) $options['data']['track_by_fingerprint'],
            'attr'  => [
                'tooltip' => 'mautic.page.config.form.track.by.fingerprint.tooltip',
            ],
        ]);

        $builder->add(
            'google_analytics_event',
            'yesno_button_group',
            [
                'label' => 'mautic.page.config.form.event.google.analytics.enabled',
                'data'  => (bool) $options['data']['google_analytics_event'],
            ]
        );
        $builder->add(
            'facebook_pixel_event',
            'yesno_button_group',
            [
                'label' => 'mautic.page.config.form.event.facebook.pixel.enabled',
                'data'  => (bool) $options['data']['facebook_pixel_event'],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'trackingconfig';
    }
}
