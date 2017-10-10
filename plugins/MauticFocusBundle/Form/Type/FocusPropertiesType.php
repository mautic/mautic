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

class FocusPropertiesType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Type specific
        switch ($options['focus_style']) {
            case 'bar':
                $builder->add(
                    'allow_hide',
                    'yesno_button_group',
                    [
                        'label' => 'mautic.focus.form.bar.allow_hide',
                        'data'  => (isset($options['data']['allow_hide'])) ? $options['data']['allow_hide'] : true,
                        'attr'  => [
                            'onchange' => 'Mautic.focusUpdatePreview()',
                        ],
                    ]
                );

                $builder->add(
                    'push_page',
                    'yesno_button_group',
                    [
                        'label' => 'mautic.focus.form.bar.push_page',
                        'attr'  => [
                            'tooltip'  => 'mautic.focus.form.bar.push_page.tooltip',
                            'onchange' => 'Mautic.focusUpdatePreview()',
                        ],
                        'data' => (isset($options['data']['push_page'])) ? $options['data']['push_page'] : true,
                    ]
                );

                $builder->add(
                    'sticky',
                    'yesno_button_group',
                    [
                        'label' => 'mautic.focus.form.bar.sticky',
                        'attr'  => [
                            'tooltip'  => 'mautic.focus.form.bar.sticky.tooltip',
                            'onchange' => 'Mautic.focusUpdatePreview()',
                        ],
                        'data' => (isset($options['data']['sticky'])) ? $options['data']['sticky'] : true,
                    ]
                );

                $builder->add(
                    'size',
                    'choice',
                    [
                        'choices' => [
                            'large'   => 'mautic.focus.form.bar.size.large',
                            'regular' => 'mautic.focus.form.bar.size.regular',
                        ],
                        'label'      => 'mautic.focus.form.bar.size',
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => [
                            'class'    => 'form-control',
                            'onchange' => 'Mautic.focusUpdatePreview()',
                        ],
                        'required'    => false,
                        'empty_value' => false,
                    ]
                );

                $choices = [
                    'top'    => 'mautic.focus.form.placement.top',
                    'bottom' => 'mautic.focus.form.placement.bottom',
                ];
                break;
            case 'modal':
                $choices = [
                    'top'    => 'mautic.focus.form.placement.top',
                    'middle' => 'mautic.focus.form.placement.middle',
                    'bottom' => 'mautic.focus.form.placement.bottom',
                ];
                break;
            case 'notification':
                $choices = [
                    'top_left'     => 'mautic.focus.form.placement.top_left',
                    'top_right'    => 'mautic.focus.form.placement.top_right',
                    'bottom_left'  => 'mautic.focus.form.placement.bottom_left',
                    'bottom_right' => 'mautic.focus.form.placement.bottom_right',
                ];
                break;
            case 'page':
                break;
        }

        if (!empty($choices)) {
            $builder->add(
                'placement',
                'choice',
                [
                    'choices'    => $choices,
                    'label'      => 'mautic.focus.form.placement',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'    => 'form-control',
                        'onchange' => 'Mautic.focusUpdatePreview()',
                    ],
                    'required'    => false,
                    'empty_value' => false,
                ]
            );
        }
    }

    public function getName()
    {
        return 'focus_properties';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(['focus_style']);

        $resolver->setDefaults(
            [
                'label' => false,
            ]
        );
    }
}
