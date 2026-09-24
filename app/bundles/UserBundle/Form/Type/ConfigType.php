<?php

namespace Mautic\UserBundle\Form\Type;

use Mautic\ConfigBundle\Form\Type\ConfigFileType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\UserBundle\Entity\OidcSubjectIdRepository;
use Mautic\UserBundle\Security\OIDC\ClientCredentials;
use Mautic\UserBundle\Security\OIDC\Settings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<array<mixed>>
 */
final class ConfigType extends AbstractType
{
    private const DISABLED_IF = '{"config_userconfig_open_id_is_enabled_0":"checked"}';

    public function __construct(
        private readonly CoreParametersHelper $parameters,
        private readonly TranslatorInterface $translator,
        private readonly Settings $config,
        private readonly ClientCredentials $clientCredentials,
        private readonly OidcSubjectIdRepository $subjectIdRepository,
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
                'label_attr' => ['class' => 'control-label'],
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
                'label_attr' => ['class' => 'control-label'],
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
                'label_attr' => ['class' => 'control-label'],
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
                'label_attr' => ['class' => 'control-label'],
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
                'label_attr' => ['class' => 'control-label'],
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
                'label_attr' => ['class' => 'control-label'],
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
                'empty_data' => 'FirstName',
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
                'empty_data' => 'LastName',
            ]
        );

        $builder->add(
            'saml_idp_default_role',
            RoleListType::class,
            [
                'label'      => 'mautic.user.config.form.saml.idp.default_role',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'            => 'form-control',
                    'data-placeholder' => $this->translator->trans('mautic.user.config.form.saml.idp.disable_creation'),
                    'tooltip'          => 'mautic.user.config.form.saml.idp.default_role.tooltip',
                ],
                'required'    => false,
                'placeholder' => '',
            ]
        );
    
        $requiredIfOpenIdIsEnabled = static function ($value, ExecutionContextInterface $context): void {
            if ($context->getObject()->getParent()->getData()['open_id_is_enabled'] && null === $value) {
                $context->addViolation('mautic.core.value.required');
            }
        };

        $secretConstraints = [new Type(type: 'string')];
        if (!$this->clientCredentials->getClientSecret()) {
            $secretConstraints[] = new Callback($requiredIfOpenIdIsEnabled);
        }

        $builder->add(
            'open_id_is_enabled',
            YesNoButtonGroupType::class,
            [
                'label'       => 'mautic.open_id.config.is_enabled',
                'data'        => $this->config->isEnabled(),
                'constraints' => [
                    new NotBlank(message: 'mautic.core.value.required'),
                    new Choice(choices: [0, 1]),
                ],
            ]
        );

        $builder->add(
            'open_id_is_required',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.open_id.config.is_required',
                'data'  => $this->config->isRequired(),
                'attr'  => [
                    'data-disable-on' => self::DISABLED_IF,
                    'tooltip'      => 'mautic.open_id.config.is_required.tooltip',
                ],
                'constraints' => [
                    new Callback(callback: $requiredIfOpenIdIsEnabled),
                    new Choice(choices: [0, 1]),
                ],
            ]
        );

        $builder->add(
            'open_id_mapping_field',
            TextType::class,
            [
                'label'      => 'mautic.open_id.config.mapping_field',
                'label_attr' => ['class' => 'control-label'],
                'data'       => $this->clientCredentials->getMappingField(),
                'attr'       => [
                    'class'        => 'form-control',
                    'data-disable-on' => self::DISABLED_IF,
                ],
                'constraints' => [
                    new Callback(callback: $requiredIfOpenIdIsEnabled),
                    new Type(type: 'string'),
                ],
            ]
        );

        $builder->add(
            'open_id_is_user_registration_allowed',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.open_id.config.is_user_registration_allowed',
                'data'  => $this->config->isUserRegistrationAllowed(),
                'attr'  => [
                    'data-disable-on' => self::DISABLED_IF,
                    'tooltip'      => 'mautic.open_id.config.is_user_registration_allowed.tooltip',
                ],
                'constraints' => [
                    new Callback(callback: $requiredIfOpenIdIsEnabled),
                    new Choice(choices: [0, 1]),
                ],
            ]
        );

        $builder->add(
            'open_id_registered_user_role',
            RoleListType::class,
            [
                'label'      => 'mautic.open_id.config.registered_user_default_role',
                'data'       => $this->config->getRegisteredUserRoleId() ?? 0,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-disable-on' => self::DISABLED_IF,
                    'tooltip'      => 'mautic.open_id.config.registered_user_default_role.tooltip',
                ],
                'constraints' => [
                    new Callback(callback: static function ($value, ExecutionContextInterface $context): void {
                        if ($context->getObject()->getParent()->getData()['open_id_is_user_registration_allowed'] && null === $value) {
                            $context->addViolation('mautic.core.value.required');
                        }
                    }),
                    new Positive(), // we cant get the values from the RoleListType, so positive is our best choice
                ],
            ]
        );

        $builder->add(
            'open_id_client_url',
            UrlType::class,
            [
                'label'            => 'mautic.open_id.config.client_url',
                'label_attr'       => ['class' => 'control-label'],
                'default_protocol' => null,
                'data'             => $this->clientCredentials->getClientUrl(),
                'attr'             => [
                    'class'        => 'form-control',
                    'data-disable-on' => self::DISABLED_IF,
                ],
                'constraints' => [
                    new Callback(callback: $requiredIfOpenIdIsEnabled),
                    new Url(),
                ],
            ]
        );

        $builder->add(
            'open_id_client_id',
            TextType::class,
            [
                'label'      => 'mautic.open_id.config.client_id',
                'label_attr' => ['class' => 'control-label'],
                'data'       => $this->clientCredentials->getClientId(),
                'attr'       => [
                    'class'        => 'form-control',
                    'data-disable-on' => self::DISABLED_IF,
                ],
                'constraints' => [
                    new Callback(callback: $requiredIfOpenIdIsEnabled),
                    new Type(type: 'string'),
                ],
            ]
        );

        // the constraints are set only if the client secret is not set
        // otherwise the field is empty and the value is injected by the subscriber
        $clientSecret = $this->clientCredentials->getClientSecret();
        $builder->add(
            'open_id_client_secret',
            PasswordType::class,
            [
                'label'      => 'mautic.open_id.config.client_secret',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'placeholder'  => $clientSecret ? str_repeat('*', 256) : '',
                    'data-disable-on' => self::DISABLED_IF,
                ],
                'required'    => !$this->clientCredentials->getClientSecret(),
                'constraints' => $secretConstraints,
            ]
        );

        $builder->addEventListener(FormEvents::PRE_SUBMIT, $this->onPreSubmit(...));
        $builder->addEventListener(FormEvents::POST_SUBMIT, $this->onPostSubmit(...));

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function onPreSubmit(PreSubmitEvent $event): void
    {
        $data = $event->getData();
        if (empty($data['open_id_client_secret'])) {
            $event->setData(array_merge($data, ['open_id_client_secret' => $this->clientCredentials->getClientSecret()]));
        }
    }

    public function onPostSubmit(PostSubmitEvent $event): void
    {
        $data = $event->getData();
        if ($data['open_id_mapping_field'] !== $this->clientCredentials->getMappingField()) {
            $this->subjectIdRepository->createQueryBuilder('s')
                ->delete()
                ->getQuery()
                ->execute();
        }
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
