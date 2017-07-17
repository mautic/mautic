<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class FormButtonsType.
 */
class FormButtonsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($options['pre_extra_buttons'] as $btn) {
            $type = (empty($btn['type'])) ? 'button' : 'submit';
            $builder->add(
                $btn['name'],
                $type,
                [
                    'label' => $btn['label'],
                    'attr'  => $btn['attr'],
                ]
            );
        }

        if (!empty($options['cancel_text'])) {
            $builder->add(
                'cancel',
                $options['cancel_type'],
                [
                    'label' => $options['cancel_text'],
                    'attr'  => array_merge(
                        $options['cancel_attr'],
                        [
                            'class'   => $options['cancel_class'],
                            'icon'    => $options['cancel_icon'],
                            'onclick' => $options['cancel_onclick'],
                        ]
                    ),
                ]
            );
        }

        if (!empty($options['save_text'])) {
            $builder->add(
                'save',
                $options['save_type'],
                [
                    'label' => $options['save_text'],
                    'attr'  => array_merge(
                        $options['save_attr'],
                        [
                            'class'   => $options['save_class'],
                            'icon'    => $options['save_icon'],
                            'onclick' => $options['save_onclick'],
                        ]
                    ),
                ]
            );
        }

        if (!empty($options['apply_text'])) {
            $builder->add(
                'apply',
                $options['apply_type'],
                [
                    'label' => $options['apply_text'],
                    'attr'  => array_merge(
                        $options['apply_attr'],
                        [
                            'class'   => $options['apply_class'],
                            'icon'    => $options['apply_icon'],
                            'onclick' => $options['apply_onclick'],
                        ]
                    ),
                ]
            );
        }

        foreach ($options['post_extra_buttons'] as $btn) {
            $type = (empty($btn['type'])) ? 'button' : 'submit';
            $builder->add(
                $btn['name'],
                $type,
                [
                    'label' => $btn['label'],
                    'attr'  => $btn['attr'],
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'apply_text'         => 'mautic.core.form.apply',
            'apply_icon'         => 'fa fa-check text-success',
            'apply_class'        => 'btn btn-default btn-apply',
            'apply_onclick'      => false,
            'apply_attr'         => [],
            'apply_type'         => 'submit',
            'save_text'          => 'mautic.core.form.saveandclose',
            'save_icon'          => 'fa fa-save',
            'save_class'         => 'btn btn-default btn-save',
            'save_onclick'       => false,
            'save_attr'          => [],
            'save_type'          => 'submit',
            'cancel_text'        => 'mautic.core.form.cancel',
            'cancel_icon'        => 'fa fa-times text-danger',
            'cancel_class'       => 'btn btn-default btn-cancel',
            'cancel_onclick'     => false,
            'cancel_attr'        => [],
            'cancel_type'        => 'submit',
            'mapped'             => false,
            'label'              => false,
            'required'           => false,
            'pre_extra_buttons'  => [],
            'post_extra_buttons' => [],
            'container_class'    => 'bottom-form-buttons',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'form_buttons';
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['containerClass'] = $options['container_class'];
    }
}
