<?php

namespace Mautic\CampaignBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<mixed>
 */
class ConfigType extends AbstractType
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'campaign_time_wait_on_event_false',
            ChoiceType::class,
            [
                'label'      => 'mautic.campaignconfig.campaign_time_wait_on_event_false',
                'label_attr' => ['class' => 'control-label'],
                'help'       => 'mautic.campaignconfig.campaign_time_wait_on_event_false_help',
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

        $builder->add(
            'campaign_email_stats_enabled',
            YesNoButtonGroupType::class,
            [
                'label'      => 'mautic.campaignconfig.campaign_email_stats_enabled',
                'label_attr' => ['class' => 'control-label'],
                'data'       => $options['data']['campaign_email_stats_enabled'] ?? true,
                'required'   => false,
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.campaignconfig.campaign_email_stats_enabled.tooltip',
                ],
            ]
        );

        $builder->add(
            'peak_interaction_timer_best_default_hour_start',
            NumberType::class,
            [
                'label'      => 'mautic.config.peak_interaction_timer.best_default_hour_start',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.config.peak_interaction_timer.best_default_hour_start.tooltip',
                ],
                'data'        => $options['data']['peak_interaction_timer_best_default_hour_start'] ?? 9,
                'constraints' => [
                    new Range([
                        'min' => 0,
                        'max' => 23,
                    ]),
                ],
            ]
        );

        $builder->add(
            'peak_interaction_timer_best_default_hour_end',
            NumberType::class,
            [
                'label'      => 'mautic.config.peak_interaction_timer.best_default_hour_end',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.config.peak_interaction_timer.best_default_hour_end.tooltip',
                ],
                'data'        => $options['data']['peak_interaction_timer_best_default_hour_end'] ?? 12,
                'constraints' => [
                    new Range([
                        'min' => 0,
                        'max' => 23,
                    ]),
                    new Callback(
                        function ($hourEnd, ExecutionContextInterface $context): void {
                            $data      = $context->getRoot()->getData();
                            $hourStart = $data['campaignconfig']['peak_interaction_timer_best_default_hour_start'] ?? null;
                            if (null !== $hourStart && null !== $hourEnd && $hourStart >= $hourEnd) {
                                $context->buildViolation('mautic.config.peak_interaction_timer.best_default_hour.validation.range')->addViolation();
                            }
                        }
                    ),
                ],
            ]
        );

        $builder->add(
            'peak_interaction_timer_best_default_days',
            ChoiceType::class,
            [
                'label'      => 'mautic.config.peak_interaction_timer.best_default_days',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.config.peak_interaction_timer.best_default_days.tooltip',
                ],
                'choices' => [
                    'mautic.core.date.monday'    => 1,
                    'mautic.core.date.tuesday'   => 2,
                    'mautic.core.date.wednesday' => 3,
                    'mautic.core.date.thursday'  => 4,
                    'mautic.core.date.friday'    => 5,
                    'mautic.core.date.saturday'  => 6,
                    'mautic.core.date.sunday'    => 7,
                ],
                'data'     => $options['data']['peak_interaction_timer_best_default_days'] ?? [2, 3, 4],
                'multiple' => true,
                'required' => true,
            ]
        );

        $builder->add(
            'peak_interaction_timer_cache_timeout',
            ChoiceType::class,
            [
                'label'      => 'mautic.config.peak_interaction_timer.cache_timeout',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.config.peak_interaction_timer.cache_timeout.tooltip',
                ],
                'choices' => [
                    'mautic.config.peak_interaction_timer.cache.off'                                        => 0,
                    '1 '.$this->translator->trans('mautic.campaign.event.intervalunit.d', ['%count%' => 1]) => 1440,
                    '7 '.$this->translator->trans('mautic.campaign.event.intervalunit.d', ['%count%' => 7]) => 10080,
                    '1 '.$this->translator->trans('mautic.campaign.event.intervalunit.m', ['%count%' => 1]) => 43800,
                ],
                'data'        => $options['data']['peak_interaction_timer_cache_timeout'] ?? 43800,
                'constraints' => [
                    new GreaterThanOrEqual([
                        'value' => 0,
                    ]),
                ],
            ]
        );

        $builder->add(
            'peak_interaction_timer_fetch_interactions_from',
            ChoiceType::class,
            [
                'label'      => 'mautic.config.peak_interaction_timer.fetch_interactions_from',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.config.peak_interaction_timer.fetch_interactions_from.tooltip',
                ],
                'choices' => [
                    'mautic.config.peak_interaction_timer.fetch.from_30_days' => '-30 days',
                    'mautic.config.peak_interaction_timer.fetch.from_60_days' => '-60 days',
                    'mautic.config.peak_interaction_timer.fetch.from_90_days' => '-90 days',
                ],
                'data' => $options['data']['peak_interaction_timer_fetch_interactions_from'] ?? '-60 days',
            ]
        );

        $builder->add(
            'peak_interaction_timer_fetch_limit',
            NumberType::class,
            [
                'label'      => 'mautic.config.peak_interaction_timer.fetch_limit',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.config.peak_interaction_timer.fetch_limit.tooltip',
                ],
                'data'        => $options['data']['peak_interaction_timer_fetch_limit'] ?? 50,
                'constraints' => [
                    new GreaterThanOrEqual([
                        'value' => 10,
                    ]),
                ],
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'campaignconfig';
    }
}
