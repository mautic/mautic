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

class ContentType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add(
            'headline',
            'text',
            [
                'label'      => 'mautic.focus.form.headline',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'onblur' => 'Mautic.focusUpdatePreview()',
                    'data-show-on' => '{"focus_html_mode_1":""}',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'tagline',
            'text',
            [
                'label'      => 'mautic.focus.form.tagline',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'placeholder' => 'mautic.core.optional',
                    'onblur'     => 'Mautic.focusUpdatePreview()',
                    'data-show-on' => '{"focus_html_mode_1":""}',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'link_text',
            'text',
            [
                'label'      => 'mautic.focus.form.link_text',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'onblur' => 'Mautic.focusUpdatePreview()',
                    'data-show-on' => '{"focus_html_mode_1":""}',
                ],
            ]
        );

        $builder->add(
            'link_url',
            'text',
            [
                'label'      => 'mautic.focus.form.link_url',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'onblur' => 'Mautic.focusUpdatePreview()',
                    'data-show-on' => '{"focus_html_mode_1":""}',
                ],
            ]
        );

        $builder->add(
            'link_new_window',
            'yesno_button_group',
            [
                'label' => 'mautic.focus.form.link_new_window',
                'data'  => (isset($options['link_new_window'])) ? $options['link_new_window'] : true,
                'attr'  => [
                    'onchange' => 'Mautic.focusUpdatePreview()',
                    'data-show-on' => '{"focus_html_mode_1":""}',
                ],
            ]
        );

        $builder->add(
            'font',
            'choice',
            [
                'choices' => [
                    'Arial, Helvetica, sans-serif'                             => 'Arial',
                    '\'Arial Black\', Gadget, sans-serif'                      => 'Arial Black',
                    '\'Arial Narrow\', sans-serif'                             => 'Arial Narrow',
                    'Century Gothic, sans-serif'                               => 'Century Gothic',
                    'Copperplate / Copperplate Gothic Light, sans-serif'       => 'Copperplate Gothic Light',
                    '\'Courier New\', Courier, monospace'                      => 'Courier New',
                    'Georgia, Serif'                                           => 'Georgia',
                    'Impact, Charcoal, sans-serif'                             => 'Impact',
                    '\'Lucida Console\', Monaco, monospace'                    => 'Lucida Console',
                    '\'Lucida Sans Unicode\', \'Lucida Grande\', sans-serif'   => 'Lucida Sans Unicode',
                    '\'Palatino Linotype\', \'Book Antiqua\', Palatino, serif' => 'Palatino',
                    'Tahoma, Geneva, sans-serif'                               => 'Tahoma',
                    '\'Times New Roman\', Times, serif'                        => 'Times New Roman',
                    '\'Trebuchet MS\', Helvetica, sans-serif'                  => 'Trebuchet MS',
                    'Verdana, Geneva, sans-serif'                              => 'Verdana',
                ],
                'label'      => 'mautic.focus.form.font',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'onchange' => 'Mautic.focusUpdatePreview()',
                    'data-show-on' => '{"focus_html_mode_1":""}',
                ],
                'required'    => false,
                'empty_value' => false,
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'focus_content';
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
