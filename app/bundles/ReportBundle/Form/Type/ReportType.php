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

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\ReportBundle\Entity\Report;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ReportType.
 */
class ReportType extends AbstractType
{
    /**
     * Factory object.
     *
     * @var \Mautic\CoreBundle\Factory\MauticFactory
     */
    private $factory;

    /**
     * Translator object.
     *
     * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    private $translator;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->translator = $factory->getTranslator();
        $this->factory    = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['description' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('report', $options));

        // Only add these fields if we're in edit mode
        if (!$options['read_only']) {
            $builder->add(
                'name',
                'text',
                [
                    'label'      => 'mautic.core.name',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control'],
                    'required'   => true,
                ]
            );

            $builder->add(
                'description',
                'textarea',
                [
                    'label'      => 'mautic.core.description',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control editor'],
                    'required'   => false,
                ]
            );

            $builder->add('isPublished', 'yesno_button_group');

            $data = $options['data']->getSystem();
            $builder->add(
                'system',
                'yesno_button_group',
                [
                    'label' => 'mautic.report.report.form.issystem',
                    'data'  => $data,
                    'attr'  => [
                        'tooltip' => 'mautic.report.report.form.issystem.tooltip',
                    ],
                ]
            );

            // Quickly build the table source list for use in the selector
            $tables = $this->buildTableSourceList($options['table_list']);

            // Build a list of data sources
            $builder->add(
                'source',
                'choice',
                [
                    'choices'     => $tables,
                    'expanded'    => false,
                    'multiple'    => false,
                    'label'       => 'mautic.report.report.form.source',
                    'label_attr'  => ['class' => 'control-label'],
                    'empty_value' => false,
                    'required'    => false,
                    'attr'        => [
                        'class'    => 'form-control',
                        'tooltip'  => 'mautic.report.report.form.source.help',
                        'onchange' => 'Mautic.updateReportSourceData(this.value)',
                    ],
                ]
            );

            /** @var \Mautic\ReportBundle\Model\ReportModel $model */
            $model        = $this->factory->getModel('report');
            $tableList    = $options['table_list'];
            $formModifier = function (FormInterface $form, $source, $currentColumns, $currentGraphs, $formData) use ($model, $tables, $tableList) {
                if (empty($source)) {
                    reset($tables);
                    $firstGroup = key($tables);
                    reset($tables[$firstGroup]);
                    $source = key($tables[$firstGroup]);
                }

                $columns           = $model->getColumnList($source);
                $filters           = $model->getFilterList($source);
                $filterDefinitions = htmlspecialchars(json_encode($filters->definitions), ENT_QUOTES, 'UTF-8');
                $operatorHtml      = htmlspecialchars(json_encode($filters->operatorHtml), ENT_QUOTES, 'UTF-8');

                if (is_array($currentColumns)) {
                    $orderColumns = array_values($currentColumns);
                    $order        = htmlspecialchars(json_encode($orderColumns), ENT_QUOTES, 'UTF-8');
                } else {
                    $order = '[]';
                }

                // Build the columns selector
                $form->add(
                    'columns',
                    'choice',
                    [
                        'choices'    => $columns->choices,
                        'label'      => false,
                        'label_attr' => ['class' => 'control-label'],
                        'required'   => false,
                        'multiple'   => true,
                        'expanded'   => false,
                        'attr'       => [
                            'class'         => 'form-control multiselect',
                            'data-order'    => $order,
                            'data-sortable' => 'true',
                        ],
                    ]
                );

                // Build the columns selector
                $form->add(
                    'groupBy',
                    'choice',
                    [
                        'choices'    => $columns->choices,
                        'label'      => false,
                        'label_attr' => ['class' => 'control-label'],
                        'required'   => false,
                        'multiple'   => true,
                        'expanded'   => false,
                        'attr'       => [
                            'class'         => 'form-control multiselect',
                            'data-order'    => $order,
                            'data-sortable' => 'true',
                            'onchange'      => 'Mautic.checkSelectedGroupBy()',
                        ],
                    ]
                );

                // Build the filter selector
                $form->add(
                    'filters',
                    'report_filters',
                    [
                        'type'    => 'filter_selector',
                        'label'   => false,
                        'options' => [
                            'filterList'   => $filters->choices,
                            'operatorList' => $filters->operatorChoices,
                            'required'     => false,
                        ],
                        'allow_add'    => true,
                        'allow_delete' => true,
                        'prototype'    => true,
                        'required'     => false,
                        'attr'         => [
                            'data-filter-definitions' => $filterDefinitions,
                            'data-filter-operators'   => $operatorHtml,
                        ],
                        'filters' => $filters->definitions,
                        'report'  => $formData,
                    ]
                );

                // Build the filter selector
                $form->add(
                    'aggregators',
                    'collection',
                    [
                        'type'    => 'aggregator',
                        'label'   => false,
                        'options' => [
                            'columnList' => $columns->choices,
                            'required'   => false,
                        ],
                        'allow_add'     => true,
                        'allow_delete'  => true,
                        'prototype'     => true,
                        'required'      => false,
                        'entry_options' => ['label' => false],
                    ]
                );

                $form->add(
                    'tableOrder',
                    'collection',
                    [
                        'type'    => 'table_order',
                        'label'   => false,
                        'options' => [
                            'columnList' => $columns->choices,
                            'required'   => false,
                        ],
                        'allow_add'    => true,
                        'allow_delete' => true,
                        'prototype'    => true,
                        'required'     => false,
                    ]
                );

                // Templates for values
                $form->add(
                    'value_template_yesno',
                    'yesno_button_group',
                    [
                        'label'  => false,
                        'mapped' => false,
                        'attr'   => [
                            'class' => 'filter-value',
                        ],
                        'data'        => 1,
                        'choice_list' => new ChoiceList(
                            [0, 1],
                            ['mautic.core.form.no', 'mautic.core.form.yes']
                        ),
                    ]
                );

                $graphList = $model->getGraphList($source);
                if (is_array($currentGraphs)) {
                    $orderColumns = array_values($currentGraphs);
                    $order        = htmlspecialchars(json_encode($orderColumns), ENT_QUOTES, 'UTF-8');
                } else {
                    $order = '[]';
                }

                $form->add(
                    'graphs',
                    'choice',
                    [
                        'choices'    => $graphList->choices,
                        'label'      => 'mautic.report.report.form.graphs',
                        'label_attr' => ['class' => 'control-label'],
                        'required'   => false,
                        'multiple'   => true,
                        'expanded'   => false,
                        'attr'       => [
                            'class'         => 'form-control multiselect',
                            'data-order'    => $order,
                            'data-sortable' => 'true',
                        ],
                    ]
                );
            };

            $builder->addEventListener(
                FormEvents::PRE_SET_DATA,
                function (FormEvent $event) use ($formModifier) {
                    $data = $event->getData();
                    $formModifier($event->getForm(), $data->getSource(), $data->getColumns(), $data->getGraphs(), $data);
                }
            );

            $builder->addEventListener(
                FormEvents::PRE_SUBMIT,
                function (FormEvent $event) use ($formModifier) {
                    $data = $event->getData();
                    $graphs = (isset($data['graphs'])) ? $data['graphs'] : [];
                    $columns = (isset($data['columns'])) ? $data['columns'] : [];
                    $formModifier($event->getForm(), $data['source'], $columns, $graphs, $data);
                }
            );

            $builder->add('buttons', 'form_buttons');
        }

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Mautic\ReportBundle\Entity\Report',
                'table_list' => [],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'report';
    }

    /**
     * Extracts the keys from the table_list option and builds an array of tables for the select list.
     *
     * @param array $tables Array with the table list and columns
     *
     * @return array
     */
    private function buildTableSourceList($tables)
    {
        $temp = array_keys($tables);

        // Create an array of tables, the key is the value stored in the database and the value is what the user sees
        $list = [];

        foreach ($temp as $table) {
            $list['mautic.report.group.'.$tables[$table]['group']][$table] = $tables[$table]['display_name'];
        }

        return $list;
    }
}
