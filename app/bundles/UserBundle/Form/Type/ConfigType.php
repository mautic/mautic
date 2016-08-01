<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Mautic\CoreBundle\Form\DataTransformer\ArrayStringTransformer;

/**
 * Class ConfigType
 *
 * @package Mautic\UserBundle\Form\Type
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
            'saml_enabled',
            'yesno_button_group',
            array(
                'label' => 'mautic.user.config.form.saml.enabled',
                'attr'  => array(
                    'tooltip' => 'mautic.user.config.form.saml.enabled.tooltip'
                )
            )
        );

        $builder->add(
            'idp_entity_id',
            'text',
            array(
                'label'       => 'mautic.user.config.form.saml.idp.entity_id',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class' => 'form-control',
                    'tooltip' => 'mautic.user.config.form.saml.idp.entity_id.tooltip'
                )
            )
        );
        $builder->add(
            'idp_login_url',
            'text',
            array(
                'label'       => 'mautic.user.config.form.saml.idp.login_url',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class' => 'form-control',
                    'tooltip' => 'mautic.user.config.form.saml.idp.login_url.tooltip'
                ),
                'required' => false
            )
        );
        $builder->add(
            'idp_logout_url',
            'text',
            array(
                'label'       => 'mautic.user.config.form.saml.idp.logout_url',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class' => 'form-control',
                    'tooltip' => 'mautic.user.config.form.saml.idp.logout_url.tooltip'
                ),
                'required' => false
            )
        );
        $builder->add(
            'idp_ceritificate',
            'textarea',
            array(
                'label'       => 'mautic.user.config.form.saml.idp.certificate',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class' => 'form-control',
                    'tooltip' => 'mautic.user.config.form.saml.idp.certificate.tooltip',
                    'rows' => 10,
                )
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'saml_config';
    }
}