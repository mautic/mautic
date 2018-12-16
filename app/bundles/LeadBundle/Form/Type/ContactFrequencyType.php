<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class MergeType.
 */
class ContactFrequencyType extends AbstractType
{
    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * ContactFrequencyType constructor.
     *
     * @param CoreParametersHelper $coreParametersHelper
     */
    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $showContactCategories = $this->coreParametersHelper->getParameter('show_contact_categories');
        $showContactSegments   = $this->coreParametersHelper->getParameter('show_contact_segments');
        // var_dump($options['data'], $options['channels']);die;
        if (!empty($options['channels'])) {
            $builder->add(
                'lead_channels',
                ContactChannelsType::class,
                [
                    'channels' => $options['channels'],
                    'data'     => $options['data']['lead_channels'],
                ]
            );

//
//            if (!$options['public_view'] || $showContactPreferredChannels) {
//                $builder->add(
//                    'preferred_channel',
//                    'choice',
//                    [
//                        'choices'           => $options['channels'],
//                        'choices_as_values' => true,
//                        'expanded'          => false,
//                        'multiple'          => false,
//                        'label'             => 'mautic.lead.list.frequency.preferred.channel',
//                        'label_attr'        => ['class' => 'control-label'],
//                        'empty_value'       => false,
//                        'required'          => false,
//                        'attr'              => [
//                            'class'   => 'form-control',
//                            'tooltip' => 'mautic.lead.list.frequency.preferred.channel',
//                        ],
//                    ]
//                );
//            }
//
//            if (!$options['public_view'] || $showContactFrequency || $showContactPauseDates) {
//                foreach ($options['channels'] as $channel) {
//                    $attr = (isset($options['data']['subscribed_channels']) && !in_array($channel, $options['data']['subscribed_channels']))
//                        ? ['disabled' => 'disabled'] : [];
//
//                    $builder->add(
//                        'frequency_number_'.$channel,
//                        'integer',
//                        [
//                            'precision'  => 0,
//                            'label'      => 'mautic.lead.list.frequency.number',
//                            'label_attr' => ['class' => 'text-muted fw-n label1'],
//                            'attr'       => array_merge(
//                                $attr,
//                                [
//                                    'class' => 'frequency form-control',
//                                ]
//                            ),
//                            'required' => false,
//                        ]
//                    );
//
//                    $builder->add(
//                        'frequency_time_'.$channel,
//                        'choice',
//                        [
//                            'choices' => [
//                                FrequencyRule::TIME_MINUTE   => 'mautic.core.time.minutes',
//                                FrequencyRule::TIME_DAY      => 'mautic.core.time.days',
//                                FrequencyRule::TIME_WEEK     => 'mautic.core.time.weeks',
//                                FrequencyRule::TIME_MONTH    => 'mautic.core.time.months',
//                            ],
//                            'label'      => 'mautic.lead.list.frequency.times',
//                            'label_attr' => ['class' => 'text-muted fw-n frequency-label label2'],
//                            'multiple'   => false,
//                            'required'   => false,
//                            'attr'       => array_merge(
//                                $attr,
//                                [
//                                    'class' => 'form-control',
//                                ]
//                            ),
//                        ]
//                    );
//
//                    if ($options['public_view'] == false) {
//                        $attributes = array_merge(
//                            $attr,
//                            [
//                                'data-toggle' => 'date',
//                                'class'       => 'frequency-date form-control',
//                            ]
//                        );
//                        $type = 'datetime';
//                    } else {
//                        $attributes = array_merge(
//                            $attr,
//                            [
//                                'class' => 'form-control',
//                            ]
//                        );
//                        $type = 'date';
//                    }
//                    if (!$options['public_view'] || $showContactPauseDates) {
//                        $builder->add(
//                            'contact_pause_start_date_'.$channel,
//                            $type,
//                            [
//                                'widget'     => 'single_text',
//                                'label'      => false, //'mautic.lead.frequency.contact.start.date',
//                                'label_attr' => ['class' => 'text-muted fw-n label3'],
//                                'attr'       => $attributes,
//                                'format'     => 'yyyy-MM-dd',
//                                'required'   => false,
//                            ]
//                        );
//                        $builder->add(
//                            'contact_pause_end_date_'.$channel,
//                            $type,
//                            [
//                                'widget'     => 'single_text',
//                                'label'      => 'mautic.lead.frequency.contact.end.date',
//                                'label_attr' => ['class' => 'frequency-label text-muted fw-n label4'],
//                                'attr'       => $attributes,
//                                'format'     => 'yyyy-MM-dd',
//                                'required'   => false,
//                            ]
//                        );
//                    }
//                }
//            }
        }

        if (!$options['public_view']) {
            $builder->add(
                'lead_lists',
                'leadlist_choices',
                [
                    'label'      => 'mautic.lead.form.list',
                    'label_attr' => ['class' => 'control-label'],
                    'multiple'   => true,
                    'expanded'   => $options['public_view'],
                    'required'   => false,
                ]
            );
        } elseif ($showContactSegments) {
            $builder->add(
                'lead_lists',
                'leadlist_choices',
                [
                    'preference_center_only' => $options['preference_center_only'],
                    'label'                  => 'mautic.lead.form.list',
                    'label_attr'             => ['class' => 'control-label'],
                    'multiple'               => true,
                    'expanded'               => true,
                    'required'               => false,
                ]
            );
        }

        if (!$options['public_view'] || $showContactCategories) {
            $builder->add(
                'global_categories',
                'leadcategory_choices',
                [
                    'label'      => 'mautic.lead.form.categories',
                    'label_attr' => ['class' => 'control-label'],
                    'multiple'   => true,
                    'expanded'   => $options['public_view'],
                    'required'   => false,
                ]
            );
        }

        $builder->add(
            'buttons',
            'form_buttons',
            [
                'apply_text'     => false,
                'save_text'      => 'mautic.core.form.save',
                'cancel_onclick' => 'javascript:void(0);',
                'cancel_attr'    => [
                    'data-dismiss' => 'modal',
                ],
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['channels']);
        $resolver->setDefaults(
            [
                'public_view'            => false,
                'preference_center_only' => false,
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'lead_contact_frequency_rules';
    }
}
