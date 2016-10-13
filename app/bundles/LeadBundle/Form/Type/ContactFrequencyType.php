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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class MergeType.
 */
class ContactFrequencyType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'channels',
            'choice',
            [
                'choices' => [
                    'sms'   => 'mautic.sms.sms',
                    'email' => 'mautic.email.email',
                ],
                'label'       => 'mautic.lead.contact.channels',
                'label_attr'  => ['class' => 'control-label'],
                'multiple'    => true,
                'empty_value' => '',
                'attr'        => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.lead.merge.select.modal.tooltip',
                ],
                'required' => false,
            ]
        );

        $formModifier = function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            $constraints = [];
            if (!empty($data['channels'])) {
                $constraints = [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ];
            }

            $form->add(
                'frequency_number',
                'number',
                [
                    'precision'  => 0,
                    'label'      => 'mautic.lead.list.frequency.number',
                    'label_attr' => ['class' => 'control-label'],
                    'required'   => true,
                    'attr'       => [
                        'class' => 'form-control frequency',
                    ],
                    'constraints' => $constraints,
                    'required'    => false,
                ]
            );

            $form->add(
                'frequency_time',
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
                    'constraints' => $constraints,
                    'required'    => false,
                ]
            );
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
     * @return string
     */
    public function getName()
    {
        return 'lead_contact_frequency_rules';
    }
}
