<?php

namespace Mautic\CampaignBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'campaign_time_wait_on_event_false',
            ChoiceType::class,
            [
                'label'      => 'mautic.campaignconfig.campaign_time_wait_on_event_false',
                'label_attr' => ['class' => 'control-label'],
                'data'       => $options['data']['campaign_time_wait_on_event_false'],
                'choices'    => [
                    'mautic.core.never' => 'null',
                    '15 mn'             => 'PT15M',
                    '30 mn'             => 'PT30M',
                    '45 mn'             => 'PT45M',
                    '1 h'               => 'PT1H',
                    '2 h'               => 'PT2H',
                    '4 h'               => 'PT4H',
                    '8 h'               => 'PT8H',
                    '12 h'              => 'PT12H',
                    '24 h'              => 'PT1D',
                    '3 days'            => 'PT3D',
                    '5 days'            => 'PT5D',
                    '1 week'            => 'PT14D',
                    '3 months'          => 'P3M',
                ],
                'attr'              => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.campaignconfig.campaign_time_wait_on_event_false_tooltip',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'campaign_by_range',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.campaignconfig.campaign_by_range',
                'attr'  => [
                    'tooltip' => 'mautic.campaignconfig.campaign_by_range.tooltip',
                ],
                'data'  => (bool) ($options['data']['campaign_by_range'] ?? false),
            ]
        );

        $builder->add(
            'campaign_use_summary',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.campaignconfig.use_summary',
                'attr'  => [
                    'tooltip' => 'mautic.campaignconfig.use_summary.tooltip',
                ],
                'data'  => (bool) ($options['data']['campaign_use_summary'] ?? false),
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'campaignconfig';
    }
}
