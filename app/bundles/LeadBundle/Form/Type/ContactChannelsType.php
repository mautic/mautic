<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\FrequencyRule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactChannelsType extends AbstractType
{
    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

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
     * @see AbstractType::buildForm()
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $showContactFrequency         = $this->coreParametersHelper->getParameter('show_contact_frequency');
        $showContactPauseDates        = $this->coreParametersHelper->getParameter('show_contact_pause_dates');
        $showContactPreferredChannels = $this->coreParametersHelper->getParameter('show_contact_preferred_channels');

        $builder->add(
            'subscribed_channels',
            'choice',
            [
                'choices'           => $options['channels'],
                'choices_as_values' => true,
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
                'choice',
                [
                    'choices'           => $options['channels'],
                    'choices_as_values' => true,
                    'expanded'          => false,
                    'multiple'          => false,
                    'label'             => 'mautic.lead.list.frequency.preferred.channel',
                    'label_attr'        => ['class' => 'control-label'],
                    'empty_value'       => false,
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
                    'integer',
                    [
                        'precision'  => 0,
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
                    'choice',
                    [
                        'choices' => [
                            FrequencyRule::TIME_DAY   => 'mautic.core.time.days',
                            FrequencyRule::TIME_WEEK  => 'mautic.core.time.weeks',
                            FrequencyRule::TIME_MONTH => 'mautic.core.time.months',
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

                if ($options['public_view'] == false) {
                    $attributes = array_merge(
                        $attr,
                        [
                            'data-toggle' => 'date',
                            'class'       => 'frequency-date form-control',
                        ]
                    );
                    $type = 'datetime';
                } else {
                    $attributes = array_merge(
                        $attr,
                        [
                            'class' => 'form-control',
                        ]
                    );
                    $type = 'date';
                }
                if (!$options['public_view'] || $showContactPauseDates) {
                    $builder->add(
                        'contact_pause_start_date_'.$channel,
                        $type,
                        [
                            'widget'     => 'single_text',
                            'label'      => false, //'mautic.lead.frequency.contact.start.date',
                            'label_attr' => ['class' => 'text-muted fw-n label3'],
                            'attr'       => $attributes,
                            'format'     => 'yyyy-MM-dd',
                            'required'   => false,
                        ]
                    );
                    $builder->add(
                        'contact_pause_end_date_'.$channel,
                        $type,
                        [
                            'widget'     => 'single_text',
                            'label'      => 'mautic.lead.frequency.contact.end.date',
                            'label_attr' => ['class' => 'frequency-label text-muted fw-n label4'],
                            'attr'       => $attributes,
                            'format'     => 'yyyy-MM-dd',
                            'required'   => false,
                        ]
                    );
                }
            }
        }

        if (isset($options['save_button']) && $options['save_button'] === true) {
            $builder->add(
                'ids',
                'hidden'
            );

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
        }

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * @see AbstractType::configureOptions()
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
