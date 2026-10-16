<?php

namespace Mautic\UserBundle\Form\Type;

use Mautic\ConfigBundle\Form\Type\ConfigFileType;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<array<mixed>>
 */
final class ConfigType extends AbstractType
{
    public function __construct(
        private readonly CoreParametersHelper $parameters,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $samlEntityIdChoices = ['', rtrim($this->parameters->get('mautic.site_url'), '/')];
        if (!empty($this->parameters->get('mautic.subdomain_url'))) {
            $samlEntityIdChoices[] = rtrim($this->parameters->get('mautic.subdomain_url'), '/');
        }
        $builder->add('saml_idp_entity_id', ChoiceType::class,
            [
                'choices'    => array_combine($samlEntityIdChoices, $samlEntityIdChoices),
                'label'      => 'mautic.user.config.form.saml.idp_entity_id_label',
                'required'   => true,
                'multiple'   => false,
                'attr'       => [
                    'class' => 'form-control',
                ],
            ]);

        $builder->add(
            'saml_idp_metadata',
            ConfigFileType::class,
            [
                'label'      => 'mautic.user.config.form.saml.idp.metadata',
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.user.config.form.saml.idp.metadata.tooltip',
                    'rows'    => 10,
                ],
                'required'    => false,
                'constraints' => [
                    new File(mimeTypes: ['text/plain', 'text/xml', 'application/xml'], mimeTypesMessage: 'mautic.core.invalid_file_type'),
                ],
            ]
        );

        $builder->add(
            'saml_idp_own_certificate',
            ConfigFileType::class,
            [
                'label'      => 'mautic.user.config.form.saml.idp.own_certificate',
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.user.config.form.saml.idp.own_certificate.tooltip',
                ],
                'required'    => false,
                'constraints' => [
                    new File(mimeTypes: ['text/plain'], mimeTypesMessage: 'mautic.core.invalid_file_type'),
                ],
            ]
        );

        $builder->add(
            'saml_idp_own_private_key',
            ConfigFileType::class,
            [
                'label'      => 'mautic.user.config.form.saml.idp.own_private_key',
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.user.config.form.saml.idp.own_private_key.tooltip',
                ],
                'required'    => false,
                'constraints' => [
                    new File(mimeTypes: ['text/plain'], mimeTypesMessage: 'mautic.core.invalid_file_type'),
                ],
            ]
        );

        $builder->add(
            'saml_idp_own_password',
            PasswordType::class,
            [
                'label'      => 'mautic.user.config.form.saml.idp.own_password',
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.user.config.form.saml.idp.own_password.tooltip',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'saml_idp_email_attribute',
            TextType::class,
            [
                'label'      => 'mautic.user.config.form.saml.idp.attribute_email',
                'attr'       => [
                    'class' => 'form-control',
                ],
                'empty_data' => 'EmailAddress',
            ]
        );

        $builder->add(
            'saml_idp_username_attribute',
            TextType::class,
            [
                'label'      => 'mautic.user.config.form.saml.idp.attribute_username',
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
                'attr'       => [
                    'class' => 'form-control',
                ],
                'empty_data' => 'FirstName',
            ]
        );

        $builder->add(
            'saml_idp_lastname_attribute',
            TextType::class,
            [
                'label'      => 'mautic.user.config.form.saml.idp.attribute_lastname',
                'attr'       => [
                    'class' => 'form-control',
                ],
                'empty_data' => 'LastName',
            ]
        );

        $builder->add(
            'saml_idp_default_role',
            RoleListType::class,
            [
                'label'      => 'mautic.user.config.form.saml.idp.default_role',
                'attr'       => [
                    'class'            => 'form-control',
                    'data-placeholder' => $this->translator->trans('mautic.user.config.form.saml.idp.disable_creation'),
                    'tooltip'          => 'mautic.user.config.form.saml.idp.default_role.tooltip',
                ],
                'required'    => false,
                'placeholder' => '',
            ]
        );
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['entityId'] = $this->parameters->get('mautic.saml_idp_entity_id');
    }

    public function getBlockPrefix(): string
    {
        return 'userconfig';
    }
}
