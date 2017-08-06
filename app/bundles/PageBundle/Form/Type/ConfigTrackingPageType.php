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
            'google_analytics_id',
            'text',
            [
                'label' => 'mautic.page.config.form.google.analytics.id',
                'attr'  => [
                    'class'       => 'form-control',
                    'tooltip' => 'mautic.page.config.form.google.analytics.id.tooltip',
                ],
            ]
        );

        $builder->add(
            'google_analytics_trackingpage_enabled',
            'yesno_button_group',
            [
                'label' => 'mautic.page.config.form.tracking.page.enabled',
                'data'  => (bool) $options['data']['google_analytics_trackingpage_enabled'],
            ]
        );
        $builder->add(
            'google_analytics_landingpage_enabled',
            'yesno_button_group',
            [
                'label' => 'mautic.page.config.form.landing.page.enabled',
                'data'  => (bool) $options['data']['google_analytics_landingpage_enabled'],
            ]
        );
        
        $builder->add(
            'google_adwords_id',
            'text',
            [
                'label' => 'mautic.page.config.form.google.adwords.id',
                'attr'  => [
                    'class'       => 'form-control',
                ],
            ]
        );

        $builder->add(
            'google_adwords_trackingpage_enabled',
            'yesno_button_group',
            [
                'label' => 'mautic.page.config.form.tracking.page.enabled',
                'data'  => (bool) $options['data']['google_adwords_trackingpage_enabled'],
            ]
        );
        
        $builder->add(
            'google_adwords_landingpage_enabled',
            'yesno_button_group',
            [
                'label' => 'mautic.page.config.form.landing.page.enabled',
                'data'  => (bool) $options['data']['google_adwords_landingpage_enabled'],
            ]
        );


        $builder->add(
            'facebook_pixel_trackingpage_enabled',
            'yesno_button_group',
            [
                'label' => 'mautic.page.config.form.tracking.page.enabled',
                'data'  => (bool) $options['data']['facebook_pixel_trackingpage_enabled'],
            ]
        );

        $builder->add(
            'facebook_pixel_landingpage_enabled',
            'yesno_button_group',
            [
                'label' => 'mautic.page.config.form.landing.page.enabled',
                'data'  => (bool) $options['data']['facebook_pixel_landingpage_enabled'],
            ]
        );


        $builder->add(
            'facebook_pixel_id',
            'text',
            [
                'label' => 'mautic.page.config.form.facebook.pixel.id',
                'attr'  => [
                    'class'       => 'form-control',
                ],
            ]
        );


        $builder->add(
            'pixel_in_campaign_enabled',
            'yesno_button_group',
            [
                'label' => 'mautic.page.config.form.pixel.campaign.enabled',
                'data'  => (bool) $options['data']['pixel_in_campaign_enabled'],
                'attr'  => [
                    'tooltip' => 'mautic.page.config.form.pixel.campaign.enable.tooltip',
                ],
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
