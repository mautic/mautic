<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class FilterSelectorType.
 */
class FilterSelectorType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Build a list of columns
        $builder->add(
            'column',
            'choice',
            [
                'choices'     => $options['filterList'],
                'expanded'    => false,
                'multiple'    => false,
                'label'       => 'mautic.report.report.label.filtercolumn',
                'label_attr'  => ['class' => 'control-label filter-column'],
                'empty_value' => false,
                'required'    => false,
                'attr'        => [
                    'class' => 'form-control filter-columns',
                ],
            ]
        );

        $formModifier = function (FormInterface $form, $column) use ($options) {
            if (null == $column) {
                reset($options['filterList']);
                $column = key($options['filterList']);
            }
            $choices = (isset($options['operatorList'][$column])) ? $options['operatorList'][$column] : [];

            // Build a list of condition values
            $form->add(
                'condition',
                'choice',
                [
                    'choices'     => $choices,
                    'expanded'    => false,
                    'multiple'    => false,
                    'label'       => 'mautic.report.report.label.filtercondition',
                    'label_attr'  => ['class' => 'control-label filter-condition'],
                    'empty_value' => false,
                    'required'    => false,
                    'attr'        => [
                        'class' => 'form-control not-chosen',
                    ],
                ]
            );
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();
                $formModifier($event->getForm(), $data['column']);
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();
                $formModifier($event->getForm(), $data['column']);
            }
        );

        $builder->add(
            'value',
            'text',
            [
                'label'      => 'mautic.report.report.label.filtervalue',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control filter-value'],
                'required'   => false,
            ]
        );

        $builder->add(
            'dynamic',
            'yesno_button_group',
            [
                'label'      => 'mautic.report.report.label.filterdynamic',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.report.report.label.filterdynamic_tooltip',
                ],
                'required' => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars = array_replace(
            $view->vars,
            [
                'filterList' => $options['filterList'],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'filter_selector';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'filterList'    => [],
                'operatorList'  => [],
                'operatorGroup' => 'default',
            ]
        );
    }
}
