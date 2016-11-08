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

use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class MergeType.
 */
class ContactFrequencyType extends AbstractType
{
    private $leadModel;

    private $em;
    /**
     * @param LeadModel $leadModel
     */
    public function __construct(LeadModel $leadModel)
    {
        $this->leadModel = $leadModel;
    }
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $formModifier = function (FormEvent $event, $options) {
            $form = $event->getForm();
            $data = $event->getData();

            if (isset($data['channels']) && $data['channels']) {
                $form->add('doNotContactChannels',
                    'choice', [
                        'choices'    => $data['channels'],
                        'expanded'   => true,
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => ['onClick' => 'Mautic.togglePreferredChannel('.$data['leadId'].',this.value);'],
                        'multiple'   => true,
                        'label'      => 'mautic.lead.do.not.contact',
                        'required'   => false,
                        'data'       => $data['lead_channels'],
                ]);
                $form->add(
                    'preferred_channel',
                    'choice',
                    [
                        'choices'     => $data['channels'],
                        'expanded'    => false,
                        'multiple'    => false,
                        'label'       => 'mautic.lead.list.frequency.preferred.channel',
                        'label_attr'  => ['class' => 'control-label'],
                        'empty_value' => false,
                        'required'    => false,
                        'attr'        => [
                            'class'   => 'form-control',
                            'tooltip' => 'mautic.lead.list.frequency.preferred.channel',
                        ],
                    ]
                );
                foreach ($data['channels'] as $channel) {
                    $form->add(
                        'frequency_number_'.$channel,
                        'number',
                        [
                            'precision'  => 0,
                            'label'      => 'mautic.lead.list.frequency.number',
                            'label_attr' => ['class' => 'control-label'],
                            'required'   => true,
                            'attr'       => [
                                'class' => 'frequency',
                            ],
                            'required' => false,
                        ]
                    );

                    $form->add(
                        'frequency_time_'.$channel,
                        'choice',
                        [
                            'choices' => [
                                'DAY'   => 'day',
                                'WEEK'  => 'week',
                                'MONTH' => 'month',
                            ],
                            'label'      => 'mautic.lead.list.frequency.times',
                            'label_attr' => ['class' => 'control-label'],
                            'multiple'   => false,
                            'attr'       => [
                                'class' => 'form-control',
                            ],
                            'required' => false,
                        ]
                    );

                    $form->add(
                            'contact_pause_start_date_'.$channel,
                            'datetime',
                            [
                                'widget'     => 'single_text',
                                'label'      => 'mautic.lead.frequency.contact.start.date',
                                'label_attr' => ['class' => 'control-label'],
                                'attr'       => [
                                    'data-toggle' => 'date',
                                ],
                                'format'   => 'yyyy-MM-dd',
                                'required' => false,
                                'data'     => isset($data['contact_pause_start_date_'.$channel]) ? $data['contact_pause_start_date_'.$channel] : null,
                            ]
                    );
                    $form->add(
                        'contact_pause_end_date_'.$channel,
                        'datetime',
                        [
                            'widget'     => 'single_text',
                            'label'      => 'mautic.lead.frequency.contact.end.date',
                            'label_attr' => ['class' => 'control-label'],
                            'attr'       => [
                                'data-toggle' => 'date',
                            ],
                            'format'   => 'yyyy-MM-dd',
                            'required' => false,
                            'data'     => isset($data['contact_pause_end_date_'.$channel]) ? $data['contact_pause_end_date_'.$channel] : null,
                        ]
                    );
                }
                $lead      = $this->leadModel->getEntity($data['leadId']);
                $leadLists = $this->leadModel->getLists($lead);

                $lists = [];
                foreach ($leadLists as $leadList) {
                    $lists[] = $leadList->getId();
                }

                $form->add(
                    'lead_lists',
                    'leadlist_choices',
                    [
                        'label'      => 'mautic.lead.form.list',
                        'label_attr' => ['class' => 'control-label'],
                        'multiple'   => true,
                        'expanded'   => $options,
                        'required'   => false,
                        'data'       => $lists,
                    ]
                );

                $leadCategories = $this->leadModel->getLeadCategories($lead);

                $form->add(
                    'global_categories',
                    'leadcategory_choices',
                    [
                        'label'      => 'mautic.lead.form.categories',
                        'label_attr' => ['class' => 'control-label'],
                        'multiple'   => true,
                        'required'   => false,
                        'data'       => $leadCategories,
                    ]
                );
            }
        };

        // Before submit
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            $formModifier
        );

        // After submit
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            $formModifier
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

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(['channels', 'public_view']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'lead_contact_frequency_rules';
    }
}
