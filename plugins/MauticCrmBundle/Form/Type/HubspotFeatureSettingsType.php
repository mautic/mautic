<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use MauticPlugin\MauticCrmBundle\Hubspot\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<array<string, mixed>>
 */
final class HubspotFeatureSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            Config::CREATE_RECORDS,
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.hubspot.form.create_records',
                'attr'  => [
                    'tooltip' => 'mautic.hubspot.form.create_records.tooltip',
                ],
                'data'  => !empty($options['data'][Config::CREATE_RECORDS]),
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['integration']);
    }
}
