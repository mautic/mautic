<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<array<mixed>>
 */
final class OneSignalFeatureSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'subdomain_name',
            TextType::class,
            [
                'label'    => 'mautic.notification.form.subdomain_name.label',
                'required' => false,
                'attr'     => [
                    'class' => 'form-control',
                ],
            ]
        );

        $builder->add(
            'platforms',
            ChoiceType::class,
            [
                'choices' => [
                    'mautic.integration.form.platforms.ios'     => 'ios',
                    'mautic.integration.form.platforms.android' => 'android',
                ],
                'attr'              => [
                    'tooltip'      => 'mautic.integration.form.platforms.tooltip',
                    // the first feature checkbox is "mobile"
                    'data-show-on' => '{"integration_config_supportedFeatures_0":"checked"}',
                ],
                'expanded'    => true,
                'multiple'    => true,
                'label'       => 'mautic.integration.form.platforms',
                'placeholder' => false,
                'required'    => false,
            ]
        );
    }
}
