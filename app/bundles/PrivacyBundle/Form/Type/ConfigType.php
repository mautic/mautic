<?php

declare(strict_types=1);

namespace Mautic\PrivacyBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<array<mixed>>
 */
final class ConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'gravatar_enabled',
            YesNoButtonGroupType::class,
            [
                'label'      => 'mautic.privacy.config.gravatar_enabled',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.privacy.config.gravatar_enabled.tooltip',
                ],
                'data'       => (bool) ($options['data']['gravatar_enabled'] ?? false),
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'privacyconfig';
    }
}
