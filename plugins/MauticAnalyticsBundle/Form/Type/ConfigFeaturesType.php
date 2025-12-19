<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAnalyticsBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigFeaturesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'auto_asset_tracking',
            YesNoButtonGroupType::class,
            [
                'label'      => 'mauticanalytics.config.auto_asset_tracking',
                'label_attr' => ['class' => 'control-label'],
                'data'       => $options['data']['auto_asset_tracking'] ?? false,
                'required'   => false,
                'attr'       => [
                    'tooltip' => 'mauticanalytics.config.auto_asset_tracking.tooltip',
                ],
            ]
        );

        $builder->add(
            'auto_video_tracking',
            YesNoButtonGroupType::class,
            [
                'label'      => 'mauticanalytics.config.auto_video_tracking',
                'label_attr' => ['class' => 'control-label'],
                'data'       => $options['data']['auto_video_tracking'] ?? false,
                'required'   => false,
                'attr'       => [
                    'tooltip' => 'mauticanalytics.config.auto_video_tracking.tooltip',
                ],
            ]
        );
    }
}
