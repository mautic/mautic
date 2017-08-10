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
            'pixel_in_campaign_enabled',
            'yesno_button_group',
            [
                'label' => 'mautic.page.config.form.pixel.campaign.enabled',
                'data'  => (bool) $options['data']['pixel_in_campaign_enabled'],
            ]
        );

        $builder->add(
            'google_analytics_id',
            'text',
            [
                'label' => 'mautic.page.config.form.google.analytics.id',
                'attr'  => [
                    'class'        => 'form-control',
                    'data-show-on' => '{"config_trackingconfig_pixel_in_campaign_enabled_1":"checked"}',
                ],
                'required' => false,
            ]
        );
        $smtpServiceShowConditions = '{"config_emailconfig_mailer_transport":["smtp"]}';
        $builder->add(
            'google_adwords_id',
            'text',
            [
                'label' => 'mautic.page.config.form.google.adwords.id',
                'attr'  => [
                    'class'        => 'form-control',
                    'data-show-on' => '{"config_trackingconfig_pixel_in_campaign_enabled_1":"checked"}',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'facebook_pixel_id',
            'text',
            [
                'label' => 'mautic.page.config.form.facebook.pixel.id',
                'attr'  => [
                    'class'        => 'form-control',
                    'data-show-on' => '{"config_trackingconfig_pixel_in_campaign_enabled_1":"checked"}',
                ],
                'required' => false,
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
