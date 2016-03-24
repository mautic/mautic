<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebPushBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ConfigType
 *
 * @package Mautic\WebPushBundle\Form\Type
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
            'webpush_enabled',
            'yesno_button_group',
            array(
                'label' => 'mautic.webpush.config.form.webpush.enabled',
                'data'  => (bool) $options['data']['webpush_enabled'],
                'attr'  => array(
                    'tooltip' => 'mautic.webpush.config.form.webpush.enabled.tooltip'
                )
            )
        );

        $builder->add(
            'webpush_app_id',
            'text',
            array(
                'label' => 'mautic.webpush.config.form.webpush.app_id',
                'data'  => $options['data']['webpush_app_id'],
                'attr'  => array(
                    'tooltip'      => 'mautic.webpush.config.form.webpush.app_id.tooltip',
                    'class'        => 'form-control',
                    'data-show-on' => '{"config_webpushconfig_webpush_enabled_1":"checked"}',
                )
            )
        );

        $builder->add(
            'webpush_rest_api_key',
            'text',
            array(
                'label' => 'mautic.webpush.config.form.webpush.rest_api_key',
                'data'  => $options['data']['webpush_rest_api_key'],
                'attr'  => array(
                    'tooltip'      => 'mautic.webpush.config.form.webpush.rest_api_key.tooltip',
                    'class'        => 'form-control',
                    'data-show-on' => '{"config_webpushconfig_webpush_enabled_1":"checked"}',
                )
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'webpushconfig';
    }
}