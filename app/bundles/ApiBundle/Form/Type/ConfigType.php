<?php

namespace Mautic\ApiBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<mixed>
 */
class ConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'api_enabled',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.api.config.form.api.enabled',
                'data'  => isset($options['data']['api_enabled']) && (bool) $options['data']['api_enabled'],
                'help'  => 'mautic.api.config.form.api.enabled.help',
            ]
        );

        $builder->add(
            'api_enable_basic_auth',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.api.config.form.api.basic_auth_enabled',
                'data'  => isset($options['data']['api_enable_basic_auth']) && (bool) $options['data']['api_enable_basic_auth'],
                'attr'  => [
                    'tooltip' => 'mautic.api.config.form.api.basic_auth.tooltip',
                ],
            ]
        );

        $builder->add(
            'api_oauth2_access_token_lifetime',
            NumberType::class,
            [
                'label' => 'mautic.api.config.form.api.oauth2_access_token_lifetime',
                'attr'  => [
                    'tooltip'      => 'mautic.api.config.form.api.oauth2_access_token_lifetime.tooltip',
                    'class'        => 'form-control',
                    'data-show-on' => '{"config_apiconfig_api_enabled_1":"checked"}',
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
            'api_oauth2_refresh_token_lifetime',
            NumberType::class,
            [
                'label' => 'mautic.api.config.form.api.oauth2_refresh_token_lifetime',
                'attr'  => [
                    'tooltip'      => 'mautic.api.config.form.api.oauth2_refresh_token_lifetime.tooltip',
                    'class'        => 'form-control',
                    'data-show-on' => '{"config_apiconfig_api_enabled_1":"checked"}',
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
    }

    public function getBlockPrefix(): string
    {
        return 'apiconfig';
    }
}
