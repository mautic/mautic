<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFocusBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PropertiesType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'bar',
            'focus_properties',
            [
                'focus_style' => 'bar',
                'data'        => (isset($options['data']['bar'])) ? $options['data']['bar'] : [],
            ]
        );

        $builder->add(
            'modal',
            'focus_properties',
            [
                'focus_style' => 'modal',
                'data'        => (isset($options['data']['modal'])) ? $options['data']['modal'] : [],
            ]
        );

        $builder->add(
            'notification',
            'focus_properties',
            [
                'focus_style' => 'notification',
                'data'        => (isset($options['data']['notification'])) ? $options['data']['notification'] : [],
            ]
        );

        $builder->add(
            'page',
            'focus_properties',
            [
                'focus_style' => 'page',
                'data'        => (isset($options['data']['page'])) ? $options['data']['page'] : [],
            ]
        );

        $builder->add(
            'animate',
            'yesno_button_group',
            [
                'label' => 'mautic.focus.form.animate',
                'data'  => (isset($options['data']['animate'])) ? $options['data']['animate'] : true,
                'attr'  => [
                    'onchange' => 'Mautic.focusUpdatePreview()',
                ],
            ]
        );

        $builder->add(
            'link_activation',
            'yesno_button_group',
            [
                'label' => 'mautic.focus.form.activate_for_links',
                'data'  => (isset($options['data']['link_activation'])) ? $options['data']['link_activation'] : true,
                'attr'  => [
                    'data-show-on' => '{"focus_properties_when": ["leave"]}',
                ],
            ]
        );

        $builder->add(
            'colors',
            'focus_color',
            [
                'label' => false,
            ]
        );

        $builder->add(
            'content',
            'focus_content',
            [
                'label' => false,
            ]
        );

        $builder->add(
            'when',
            'choice',
            [
                'choices' => [
                    'immediately'   => 'mautic.focus.form.when.immediately',
                    'scroll_slight' => 'mautic.focus.form.when.scroll_slight',
                    'scroll_middle' => 'mautic.focus.form.when.scroll_middle',
                    'scroll_bottom' => 'mautic.focus.form.when.scroll_bottom',
                    'leave'         => 'mautic.focus.form.when.leave',
                ],
                'label'       => 'mautic.focus.form.when',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'expanded'    => false,
                'multiple'    => false,
                'required'    => false,
                'empty_value' => false,
            ]
        );

        $builder->add(
            'timeout',
            'text',
            [
                'label'      => 'mautic.focus.form.timeout',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'          => 'form-control',
                    'postaddon_text' => 'sec',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'frequency',
            'choice',
            [
                'choices' => [
                    'everypage' => 'mautic.focus.form.frequency.everypage',
                    'once'      => 'mautic.focus.form.frequency.once',
                    'q2min'     => 'mautic.focus.form.frequency.q2m',
                    'q15min'    => 'mautic.focus.form.frequency.q15m',
                    'hourly'    => 'mautic.focus.form.frequency.hourly',
                    'daily'     => 'mautic.focus.form.frequency.daily',
                ],
                'label'       => 'mautic.focus.form.frequency',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'expanded'    => false,
                'multiple'    => false,
                'required'    => false,
                'empty_value' => false,
            ]
        );

        $builder->add(
            'stop_after_conversion',
            'yesno_button_group',
            [
                'label' => 'mautic.focus.form.engage_after_conversion',
                'data'  => (isset($options['data']['stop_after_conversion'])) ? $options['data']['stop_after_conversion'] : true,
                'attr'  => [
                    'tooltip' => 'mautic.focus.form.engage_after_conversion.tooltip',
                ],
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'focus_entity_properties';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'label' => false,
            ]
        );
    }
}
