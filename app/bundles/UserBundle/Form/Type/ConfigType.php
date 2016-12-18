<?php

/*
 * @copyright  2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

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
            'saml_idp_metadata',
            FileType::class,
            [
                'label'      => 'mautic.user.config.form.saml.idp.metadata',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.user.config.form.saml.idp.metadata.tooltip',
                    'rows'    => 10,
                ],
                'required'    => false,
                'constraints' => [
                    new File(
                        [
                            'mimeTypes'        => ['text/plain', 'text/xml', 'application/xml'],
                            'mimeTypesMessage' => 'mautic.core.invalid_file_type',
                        ]
                    ),
                ],
            ]
        );

        $builder->add(
            'saml_idp_certificate',
            FileType::class,
            [
                'label'      => 'mautic.user.config.form.saml.idp.certificate',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.user.config.form.saml.idp.certificate.tooltip',
                ],
                'required'    => false,
                'constraints' => [
                    new File(
                        [
                            'mimeTypes'        => ['text/plain'],
                            'mimeTypesMessage' => 'mautic.core.invalid_file_type',
                        ]
                    ),
                ],
            ]
        );

        $builder->add(
            'saml_idp_email_attribute',
            TextType::class,
            [
                'label'      => 'mautic.user.config.form.saml.idp.attribute_email',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ],
            ]
        );

        $builder->add(
            'saml_idp_username_attribute',
            TextType::class,
            [
                'label'      => 'mautic.user.config.form.saml.idp.attribute_username',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'saml_idp_firstname_attribute',
            TextType::class,
            [
                'label'      => 'mautic.user.config.form.saml.idp.attribute_firstname',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'saml_idp_lastname_attribute',
            TextType::class,
            [
                'label'      => 'mautic.user.config.form.saml.idp.attribute_lastname',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'saml_idp_default_role',
            'role_list',
            [
                'label'      => 'mautic.user.config.form.saml.idp.default_role',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'required' => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'userconfig';
    }
}
