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

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
            FocusPropertiesType::class,
            [
                'focus_style' => 'bar',
                'data'        => (isset($options['data']['bar'])) ? $options['data']['bar'] : [],
            ]
        );

        $builder->add(
            'modal',
            FocusPropertiesType::class,
            [
                'focus_style' => 'modal',
                'data'        => (isset($options['data']['modal'])) ? $options['data']['modal'] : [],
            ]
        );

        $builder->add(
            'notification',
            FocusPropertiesType::class,
            [
                'focus_style' => 'notification',
                'data'        => (isset($options['data']['notification'])) ? $options['data']['notification'] : [],
            ]
        );

        $builder->add(
            'page',
            FocusPropertiesType::class,
            [
                'focus_style' => 'page',
                'data'        => (isset($options['data']['page'])) ? $options['data']['page'] : [],
            ]
        );

        $builder->add(
            'animate',
            YesNoButtonGroupType::class,
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
            YesNoButtonGroupType::class,
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
            ColorType::class,
            [
                'label' => false,
            ]
        );

        $builder->add(
            'content',
            ContentType::class,
            [
                'label' => false,
            ]
        );

        $builder->add(
            'when',
            ChoiceType::class,
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
            TextType::class,
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
            ChoiceType::class,
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
            YesNoButtonGroupType::class,
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
    public function getBlockPrefix()
    {
        return 'focus_entity_properties';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'label' => false,
            ]
        );
    }
}
