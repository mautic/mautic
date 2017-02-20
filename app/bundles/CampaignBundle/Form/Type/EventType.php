<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\Type\PropertiesTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class EventType.
 */
class EventType extends AbstractType
{
    use PropertiesTrait;

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $masks = [];

        $builder->add(
            'name',
            'text',
            [
                'label'      => 'mautic.core.name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );

        $builder->add(
            'anchor',
            'hidden',
            [
                'label' => false,
            ]
        );

        if (in_array($options['data']['eventType'], ['action', 'condition'])) {
            $label = 'mautic.campaign.form.type';

            $choices = [
                'immediate' => 'mautic.campaign.form.type.immediate',
                'interval'  => 'mautic.campaign.form.type.interval',
                'date'      => 'mautic.campaign.form.type.date',
            ];

            if ('no' == $options['data']['anchor'] && 'condition' != $options['data']['anchorEventType']
                && 'condition' != $options['data']['eventType']
            ) {
                $label .= '_inaction';

                unset($choices['immediate']);
                $choices['interval'] = $choices['interval'].'_inaction';
                $choices['date']     = $choices['date'].'_inaction';
            }

            reset($choices);
            $default = key($choices);

            $triggerMode = (empty($options['data']['triggerMode'])) ? $default : $options['data']['triggerMode'];
            $builder->add(
                'triggerMode',
                'button_group',
                [
                    'choices'     => $choices,
                    'expanded'    => true,
                    'multiple'    => false,
                    'label_attr'  => ['class' => 'control-label'],
                    'label'       => $label,
                    'empty_value' => false,
                    'required'    => false,
                    'attr'        => [
                        'onchange' => 'Mautic.campaignToggleTimeframes();',
                    ],
                    'data' => $triggerMode,
                ]
            );

            $builder->add(
                'triggerDate',
                'datetime',
                [
                    'label' => false,
                    'attr'  => [
                        'class'       => 'form-control',
                        'preaddon'    => 'fa fa-calendar',
                        'data-toggle' => 'datetime',
                    ],
                    'widget' => 'single_text',
                    'format' => 'yyyy-MM-dd HH:mm',
                ]
            );

            $data = (empty($options['data']['triggerInterval'])) ? 1 : $options['data']['triggerInterval'];
            $builder->add(
                'triggerInterval',
                'number',
                [
                    'label' => false,
                    'attr'  => [
                        'class'    => 'form-control',
                        'preaddon' => 'symbol-hashtag',
                    ],
                    'data' => $data,
                ]
            );

            $data = (!empty($options['data']['triggerIntervalUnit'])) ? $options['data']['triggerIntervalUnit'] : 'd';

            $builder->add(
                'triggerIntervalUnit',
                'choice',
                [
                    'choices' => [
                        'i' => 'mautic.campaign.event.intervalunit.choice.i',
                        'h' => 'mautic.campaign.event.intervalunit.choice.h',
                        'd' => 'mautic.campaign.event.intervalunit.choice.d',
                        'm' => 'mautic.campaign.event.intervalunit.choice.m',
                        'y' => 'mautic.campaign.event.intervalunit.choice.y',
                    ],
                    'multiple'   => false,
                    'label_attr' => ['class' => 'control-label'],
                    'label'      => false,
                    'attr'       => [
                        'class' => 'form-control',
                    ],
                    'empty_value' => false,
                    'required'    => false,
                    'data'        => $data,
                ]
            );
        }

        if (!empty($options['settings']['formType'])) {
            $this->addPropertiesType($builder, $options, $masks);
        }

        $builder->add('type', 'hidden');
        $builder->add('eventType', 'hidden');
        $builder->add(
            'anchorEventType',
            'hidden',
            [
                'mapped' => false,
                'data'   => (isset($options['data']['anchorEventType'])) ? $options['data']['anchorEventType'] : '',
            ]
        );

        $builder->add(
            'canvasSettings',
            'campaignevent_canvassettings',
            [
                'label' => false,
            ]
        );

        $update = !empty($options['data']['properties']);
        if (!empty($update)) {
            $btnValue = 'mautic.core.form.update';
            $btnIcon  = 'fa fa-pencil';
        } else {
            $btnValue = 'mautic.core.form.add';
            $btnIcon  = 'fa fa-plus';
        }

        $builder->add(
            'buttons',
            'form_buttons',
            [
                'save_text'       => $btnValue,
                'save_icon'       => $btnIcon,
                'save_onclick'    => 'Mautic.submitCampaignEvent(event)',
                'apply_text'      => false,
                'container_class' => 'bottom-form-buttons',
            ]
        );

        $builder->add(
            'campaignId',
            'hidden',
            [
                'mapped' => false,
            ]
        );

        $builder->addEventSubscriber(new CleanFormSubscriber($masks));

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['settings']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'campaignevent';
    }
}
