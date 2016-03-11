<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ConfigType
 *
 * @package Mautic\SmsBundle\Form\Type
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
            'sms_enabled',
            'yesno_button_group',
            array(
                'label' => 'mautic.sms.config.form.sms.enabled',
                'data'  => (bool) $options['data']['sms_enabled'],
                'attr'  => array(
                    'tooltip' => 'mautic.sms.config.form.sms.enabled.tooltip'
                )
            )
        );

        $builder->add(
            'sms_username',
            'text',
            array(
                'label' => 'mautic.sms.config.form.sms.username',
                'data'  => $options['data']['sms_username'],
                'attr'  => array(
                    'tooltip'      => 'mautic.sms.config.form.sms.username.tooltip',
                    'class'        => 'form-control',
                    'data-show-on' => '{"config_smsconfig_sms_enabled_1":"checked"}',
                )
            )
        );

        $builder->add(
            'sms_password',
            'text',
            array(
                'label' => 'mautic.sms.config.form.sms.password',
                'data'  => $options['data']['sms_password'],
                'attr'  => array(
                    'tooltip'      => 'mautic.sms.config.form.sms.password.tooltip',
                    'class'        => 'form-control',
                    'data-show-on' => '{"config_smsconfig_sms_enabled_1":"checked"}',
                )
            )
        );

        $builder->add(
            'sms_sending_phone_number',
            'text',
            array(
                'label' => 'mautic.sms.config.form.sms.sending_phone_number',
                'data'  => $options['data']['sms_sending_phone_number'],
                'attr'  => array(
                    'tooltip'      => 'mautic.sms.config.form.sms.sending_phone_number.tooltip',
                    'class'        => 'form-control',
                    'data-show-on' => '{"config_smsconfig_sms_enabled_1":"checked"}',
                )
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'smsconfig';
    }
}