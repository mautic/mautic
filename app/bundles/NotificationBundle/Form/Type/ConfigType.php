<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ConfigType
 *
 * @package Mautic\NotificationBundle\Form\Type
 */
class ConfigType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'notification_enabled',
            'yesno_button_group',
            array(
                'label' => 'mautic.notification.config.form.notification.enabled',
                'data'  => (bool) $options['data']['notification_enabled'],
                'attr'  => array(
                    'tooltip' => 'mautic.notification.config.form.notification.enabled.tooltip'
                )
            )
        );

        $builder->add(
            'notification_app_id',
            'text',
            array(
                'label' => 'mautic.notification.config.form.notification.app_id',
                'data'  => $options['data']['notification_app_id'],
                'attr'  => array(
                    'tooltip'      => 'mautic.notification.config.form.notification.app_id.tooltip',
                    'class'        => 'form-control',
                    'data-show-on' => '{"config_notificationconfig_notification_enabled_1":"checked"}',
                )
            )
        );

        $builder->add(
            'notification_safari_web_id',
            'text',
            array(
                'label' => 'mautic.notification.config.form.notification.safari_web_id',
                'data'  => $options['data']['notification_safari_web_id'],
                'attr'  => array(
                    'tooltip'      => 'mautic.notification.config.form.notification.safari_web_id.tooltip',
                    'class'        => 'form-control',
                    'data-show-on' => '{"config_notificationconfig_notification_enabled_1":"checked"}',
                )
            )
        );

        $builder->add(
            'notification_rest_api_key',
            'text',
            array(
                'label' => 'mautic.notification.config.form.notification.rest_api_key',
                'data'  => $options['data']['notification_rest_api_key'],
                'attr'  => array(
                    'tooltip'      => 'mautic.notification.config.form.notification.rest_api_key.tooltip',
                    'class'        => 'form-control',
                    'data-show-on' => '{"config_notificationconfig_notification_enabled_1":"checked"}',
                )
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'notificationconfig';
    }
}