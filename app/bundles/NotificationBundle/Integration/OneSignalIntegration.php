<?php


/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;

/**
 * Class OneSignalIntegration.
 */
class OneSignalIntegration extends AbstractIntegration
{
    /**
     * @var bool
     */
    protected $coreIntegration = true;

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'OneSignal';
    }

    public function getIcon()
    {
        return 'app/bundles/NotificationBundle/Assets/img/OneSignal.png';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'app_id'        => 'mautic.notification.config.form.notification.app_id',
            'safari_web_id' => 'mautic.notification.config.form.notification.safari_web_id',
            'rest_api_key'  => 'mautic.notification.config.form.notification.rest_api_key',
            'gcm_sender_id' => 'mautic.notification.config.form.notification.gcm_sender_id',
        ];
    }

    /**
     * @return array
     */
    public function getFormSettings()
    {
        return [
            'requires_callback'      => false,
            'requires_authorization' => false,
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
        if ($formArea == 'features') {
            $builder->add(
                'features',
                'choice',
                [
                    'choices' => [
                        'landing_page_enabled'         => 'mautic.notification.config.form.notification.landingpage.enabled',
                        'welcome_notification_enabled' => 'mautic.notification.config.form.notification.welcome.enabled',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => false,
                    'label_attr'  => ['class' => ''],
                    'empty_value' => false,
                    'required'    => false,
                ]
            );
        }
    }
}
