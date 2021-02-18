<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\FrequencyRule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactChannelsType extends AbstractType
{
    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $showContactFrequency         = $this->coreParametersHelper->get('show_contact_frequency');
        $showContactPauseDates        = $this->coreParametersHelper->get('show_contact_pause_dates');
        $showContactPreferredChannels = $this->coreParametersHelper->get('show_contact_preferred_channels');

        $builder->add(
            'subscribed_channels',
            ChoiceType::class,
            [
                'choices'           => $options['channels'],
                'expanded'          => true,
                'label_attr'        => ['class' => 'control-label'],
                'attr'              => ['onClick' => 'Mautic.togglePreferredChannel(this.value);'],
                'multiple'          => true,
                'label'             => false,
                'required'          => false,
            ]
        );

        if (!$options['public_view'] || $showContactPreferredChannels) {
            $builder->add(
                'preferred_channel',
                ChoiceType::class,
                [
                    'choices'           => $options['channels'],
                    'expanded'          => false,
                    'multiple'          => false,
                    'label'             => 'mautic.lead.list.frequency.preferred.channel',
                    'label_attr'        => ['class' => 'control-label'],
                    'placeholder'       => false,
                    'required'          => false,
                    'attr'              => [
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.lead.list.frequency.preferred.channel',
                    ],
                ]
            );
        }

        if (!$options['public_view'] || $showContactFrequency || $showContactPauseDates) {
            foreach ($options['channels'] as $channel) {
                $attr = (isset($options['data']['subscribed_channels']) && !in_array($channel, $options['data']['subscribed_channels']))
                    ? ['disabled' => 'disabled'] : [];

                $builder->add(
                    'frequency_number_'.$channel,
                    IntegerType::class,
                    [
                        'scale'      => 0,
                        'label'      => 'mautic.lead.list.frequency.number',
                        'label_attr' => ['class' => 'text-muted fw-n label1'],
                        'attr'       => array_merge(
                            $attr,
                            [
                                'class' => 'frequency form-control',
                            ]
                        ),
                        'required' => false,
                    ]
                );

                $builder->add(
                    'frequency_time_'.$channel,
                    ChoiceType::class,
                    [
                        'choices'           => [
                            'mautic.core.time.days'   => FrequencyRule::TIME_DAY,
                            'mautic.core.time.weeks'  => FrequencyRule::TIME_WEEK,
                            'mautic.core.time.months' => FrequencyRule::TIME_MONTH,
                        ],
                        'label'      => 'mautic.lead.list.frequency.times',
                        'label_attr' => ['class' => 'text-muted fw-n frequency-label label2'],
                        'multiple'   => false,
                        'required'   => false,
                        'attr'       => array_merge(
                            $attr,
                            [
                                'class' => 'form-control',
                            ]
                        ),
                    ]
                );

                if (false == $options['public_view']) {
                    $attributes = array_merge(
                        $attr,
                        [
                            'data-toggle' => 'date',
                            'class'       => 'frequency-date form-control',
                        ]
                    );
                } else {
                    $attributes = array_merge(
                        $attr,
                        [
                            'class' => 'form-control',
                        ]
                    );
                }

                if (!$options['public_view'] || $showContactPauseDates) {
                    $builder->add(
                        'contact_pause_start_date_'.$channel,
                        DateType::class,
                        [
                            'widget'     => 'single_text',
                            'label'      => false,
                            'label_attr' => ['class' => 'text-muted fw-n label3'],
                            'attr'       => $attributes,
                            'format'     => $options['public_view'] ? DateType::HTML5_FORMAT : 'yyyy-MM-dd',
                            'html5'      => $options['public_view'],
                            'required'   => false,
                        ]
                    );
                    $builder->add(
                        'contact_pause_end_date_'.$channel,
                        DateType::class,
                        [
                            'widget'     => 'single_text',
                            'label'      => 'mautic.lead.frequency.contact.end.date',
                            'label_attr' => ['class' => 'frequency-label text-muted fw-n label4'],
                            'attr'       => $attributes,
                            'format'     => $options['public_view'] ? DateType::HTML5_FORMAT : 'yyyy-MM-dd',
                            'html5'      => $options['public_view'],
                            'required'   => false,
                        ]
                    );
                }
            }
        }

        if (isset($options['save_button']) && true === $options['save_button']) {
            $builder->add(
                'ids',
                HiddenType::class
            );

            $builder->add(
                'buttons',
                FormButtonsType::class,
                [
                    'apply_text'     => false,
                    'save_text'      => 'mautic.core.form.save',
                    'cancel_onclick' => 'javascript:void(0);',
                    'cancel_attr'    => [
                        'data-dismiss' => 'modal',
                    ],
                ]
            );
        }

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['channels']);
        $resolver->setDefaults(
            [
                'public_view'               => false,
                'save_button'               => false,
            ]
        );
    }
}
