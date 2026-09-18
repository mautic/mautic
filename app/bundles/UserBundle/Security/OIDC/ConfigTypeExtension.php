<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\UserBundle\Form\Type\ConfigType;
use Mautic\UserBundle\Form\Type\RoleListType;
use Mautic\UserBundle\Security\OIDC\DTO\ClientCredentials;
use Mautic\UserBundle\Security\OIDC\DTO\Settings;
use Mautic\UserBundle\Security\OIDC\Repository\SubjectIdRepository;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class ConfigTypeExtension extends AbstractTypeExtension
{
    private const SHOW_IF_ENABLED = '{"config_userconfig_open_id_is_enabled_1":"checked"}';
    private Settings $config;
    private ClientCredentials $clientCredentials;
    private SubjectIdRepository $subjectIdRepository;

    public function __construct(Settings $config, ClientCredentials $clientCredentials, SubjectIdRepository $subjectIdRepository)
    {
        $this->config              = $config;
        $this->clientCredentials   = $clientCredentials;
        $this->subjectIdRepository = $subjectIdRepository;
    }

    /**
     * @return string[]
     */
    public static function getExtendedTypes(): iterable
    {
        return [ConfigType::class];
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $requiredIfOpenIdIsEnabled = static function ($value, ExecutionContextInterface $context) {
            if ($context->getObject()->getParent()->getData()['open_id_is_enabled'] && null === $value) {
                $context->addViolation('mautic.core.value.required');
            }
        };

        $secretConstraints = [new Assert\Type(['type' => 'string'])];
        if (!$this->clientCredentials->getClientSecret()) {
            $secretConstraints[] = new Assert\Callback($requiredIfOpenIdIsEnabled);
        }

        $builder->add(
            'open_id_is_enabled',
            YesNoButtonGroupType::class,
            [
                'label'       => 'mautic.open_id.config.is_enabled',
                'data'        => $this->config->isEnabled(),
                'constraints' => [
                    new Assert\NotBlank(['message' => 'mautic.core.value.required']),
                    new Assert\Choice(['choices' => [0, 1]]),
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
                    'data-show-on' => self::SHOW_IF_ENABLED,
                    'tooltip'      => 'mautic.open_id.config.is_required.tooltip',
                ],
                'constraints' => [
                    new Assert\Callback(['callback' => $requiredIfOpenIdIsEnabled]),
                    new Assert\Choice(['choices' => [0, 1]]),
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
                    'data-show-on' => self::SHOW_IF_ENABLED,
                ],
                'constraints' => [
                    new Assert\Callback(['callback' => $requiredIfOpenIdIsEnabled]),
                    new Assert\Type(['type' => 'string']),
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
                    'data-show-on' => self::SHOW_IF_ENABLED,
                    'tooltip'      => 'mautic.open_id.config.is_user_registration_allowed.tooltip',
                ],
                'constraints' => [
                    new Assert\Callback(['callback' => $requiredIfOpenIdIsEnabled]),
                    new Assert\Choice(['choices' => [0, 1]]),
                ],
            ]
        );

        $builder->add(
            'open_id_registered_user_role',
            RoleListType::class,
            [
                'label'      => 'mautic.open_id.config.registered_user_default_role',
                'data'       => $this->config->getRegisteredUserRole() ? $this->config->getRegisteredUserRole()->getId() : 0,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-show-on' => self::SHOW_IF_ENABLED,
                    'tooltip'      => 'mautic.open_id.config.registered_user_default_role.tooltip',
                ],
                'constraints' => [
                    new Assert\Callback(['callback' => static function ($value, ExecutionContextInterface $context) {
                        if ($context->getObject()->getParent()->getData()['open_id_is_user_registration_allowed'] && null === $value) {
                            $context->addViolation('mautic.core.value.required');
                        }
                    }]),
                    new Assert\Positive(), // we cant get the values from the RoleListType, so positive is our best choice
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
                    'data-show-on' => self::SHOW_IF_ENABLED,
                ],
                'constraints' => [
                    new Assert\Callback(['callback' => $requiredIfOpenIdIsEnabled]),
                    new Assert\Url(),
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
                    'data-show-on' => self::SHOW_IF_ENABLED,
                ],
                'constraints' => [
                    new Assert\Callback(['callback' => $requiredIfOpenIdIsEnabled]),
                    new Assert\Type(['type' => 'string']),
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
                    'data-show-on' => self::SHOW_IF_ENABLED,
                ],
                'required'    => !$this->clientCredentials->getClientSecret(),
                'constraints' => $secretConstraints,
            ]
        );

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
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
}
