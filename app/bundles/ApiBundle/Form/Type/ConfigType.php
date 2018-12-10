<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
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
            'api_enabled',
            'yesno_button_group',
            [
                'label' => 'mautic.api.config.form.api.enabled',
                'data'  => (bool) $options['data']['api_enabled'],
                'attr'  => [
                    'tooltip' => 'mautic.api.config.form.api.enabled.tooltip',
                ],
            ]
        );

        $builder->add(
            'api_enable_basic_auth',
            'yesno_button_group',
            [
                'label' => 'mautic.api.config.form.api.basic_auth_enabled',
                'data'  => (bool) $options['data']['api_enable_basic_auth'],
                'attr'  => [
                    'tooltip' => 'mautic.api.config.form.api.basic_auth.tooltip',
                ],
            ]
        );

        $builder->add(
            'api_oauth2_access_token_lifetime',
            'number',
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
            'number',
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

        $builder->add(
          'api_rate_limiter_limit',
          'number',
          [
            'label' => 'mautic.api.config.form.api.rate_limiter_limit',
            'attr'  => [
              'tooltip'      => 'mautic.api.config.form.api.rate_limiter_limit.tooltip',
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

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'apiconfig';
    }
}
