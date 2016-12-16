<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ConfigType.
 */
class ConfigType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'saml_enabled',
            'yesno_button_group',
            [
                'label' => 'mautic.user.config.form.saml.enabled',
                'attr'  => [
                    'tooltip' => 'mautic.user.config.form.saml.enabled.tooltip',
                ],
            ]
        );

        $builder->add(
            'idp_entity_id',
            'text',
            [
                'label'      => 'mautic.user.config.form.saml.idp.entity_id',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.user.config.form.saml.idp.entity_id.tooltip',
                ],
            ]
        );

        $builder->add(
            'idp_ceritificate',
            'textarea',
            [
                'label'      => 'mautic.user.config.form.saml.idp.certificate',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.user.config.form.saml.idp.certificate.tooltip',
                    'rows'    => 10,
                ],
            ]
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
