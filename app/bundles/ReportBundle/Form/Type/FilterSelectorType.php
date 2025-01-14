<?php

namespace Mautic\ReportBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
            ChoiceType::class,
            [
                'choices'           => array_flip($options['filterList']),
                'expanded'          => false,
                'multiple'          => false,
                'label'             => 'mautic.report.report.label.filtercolumn',
                'label_attr'        => ['class' => 'control-label filter-column'],
                'placeholder'       => false,
                'required'          => false,
                'attr'              => [
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
                ChoiceType::class,
                [
                    'choices'           => array_flip($choices),
                    'expanded'          => false,
                    'multiple'          => false,
                    'label'             => 'mautic.report.report.label.filtercondition',
                    'label_attr'        => ['class' => 'control-label filter-condition'],
                    'placeholder'       => false,
                    'required'          => false,
                    'attr'              => [
                        'class' => 'form-control not-chosen',
                    ],
                ]
            );
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $data = !empty($event->getData()) ? $event->getData()['column'] : null;
                $formModifier($event->getForm(), $data);
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $data = !empty($event->getData()) ? $event->getData()['column'] : null;
                $formModifier($event->getForm(), $data);
            }
        );

        $builder->add(
            'glue',
            ChoiceType::class,
            [
                'label'             => false,
                'label_attr'        => ['class' => 'control-label'],
                'attr'              => ['class' => 'form-control filter-glue not-chosen'],
                'required'          => false,
                'choices'           => [
                    'mautic.report.report.glue.choice.and' => 'and',
                    'mautic.report.report.glue.choice.or'  => 'or',
                ],
                'placeholder' => false,
            ]
        );

        $builder->add(
            'value',
            TextType::class,
            [
                'label'      => 'mautic.report.report.label.filtervalue',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control filter-value'],
                'required'   => false,
            ]
        );

        $builder->add(
            'dynamic',
            YesNoButtonGroupType::class,
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
    public function getBlockPrefix()
    {
        return 'filter_selector';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
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
