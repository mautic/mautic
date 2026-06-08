<?php

namespace Mautic\ReportBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Model\ReportModel;
use Mautic\ReportBundle\Scheduler\Enum\SchedulerEnum;
use Mautic\UserBundle\Form\Type\UserListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Report>
 */
class ReportType extends AbstractType
{
    public function __construct(
        private ReportModel $reportModel,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['description' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('report', $options));

        // Only add these fields if we're in edit mode
        if (!($options['attr']['readonly'] ?? false)) {
            $builder->add(
                'name',
                TextType::class,
                [
                    'label'      => 'mautic.core.name',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control'],
                    'required'   => true,
                ]
            );

            $builder->add(
                'description',
                TextareaType::class,
                [
                    'label'      => 'mautic.core.description',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control editor'],
                    'required'   => false,
                ]
            );

            $builder->add('isPublished', YesNoButtonGroupType::class, [
                'label' => 'mautic.core.form.available',
            ]);

            $data = $options['data']->getSystem();
            $builder->add(
                'system',
                YesNoButtonGroupType::class,
                [
                    'label'      => 'mautic.report.report.form.issystem',
                    'data'       => $data,
                    'attr'       => [
                        'tooltip' => 'mautic.report.report.form.issystem.tooltip',
                    ],
                    'no_label'   => 'mautic.lead.list.form.isglobal.no',
                ]
            );

            $builder->add(
                'createdBy',
                UserListType::class,
                [
                    'label'      => 'mautic.report.report.form.owner',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class' => 'form-control',
                    ],
                    'required' => false,
                    'multiple' => false,
                ]
            );
            $builder->add(
                'settings',
                ReportSettingsType::class,
                [
                    'label'      => false,
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.email.utm_tags.tooltip',
                    ],
                    'data'     => $options['data']->getSettings(),
                    'required' => false,
                ]
            );

            // Quickly build the table source list for use in the selector
            $tables = $this->buildTableSourceList($options['table_list']);

            // Build a list of data sources
            $builder->add(
                'source',
                ChoiceType::class,
                [
                    'choices'           => $tables,
                    'expanded'          => false,
                    'multiple'          => false,
                    'label'             => 'mautic.report.report.form.source',
                    'label_attr'        => ['class' => 'control-label'],
                    'placeholder'       => false,
                    'required'          => false,
                    'attr'              => [
                        'class'    => 'form-control',
                        'tooltip'  => 'mautic.report.report.form.source.help',
                        'onchange' => 'Mautic.updateReportSourceData(this.value)',
                    ],
                ]
            );

            $formModifier = function (FormInterface $form, $source, $currentColumns, $currentGraphs, $formData) use ($tables): void {
                if (empty($source)) {
                    $firstGroup           = array_key_first($tables);
                    $firstKeyInFirstGroup = array_key_first($tables[$firstGroup]);
                    $source               = $tables[$firstGroup][$firstKeyInFirstGroup];
                }

                $columns           = $this->reportModel->getColumnList($source);
                $groupByColumns    = $this->reportModel->getColumnList($source, true);
                $filters           = $this->reportModel->getFilterList($source);
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
                    ChoiceType::class,
                    [
                        'choices'           => array_flip($columns->choices),
                        'label'             => false,
                        'label_attr'        => ['class' => 'control-label'],
                        'required'          => false,
                        'multiple'          => true,
                        'expanded'          => false,
                        'attr'              => [
                            'class'         => 'form-control multiselect',
                            'data-order'    => $order,
                            'data-sortable' => 'true',
                        ],
                    ]
                );

                // Build the columns selector
                $form->add(
                    'groupBy',
                    ChoiceType::class,
                    [
                        'choices'           => array_flip($groupByColumns->choices),
                        'label'             => false,
                        'label_attr'        => ['class' => 'control-label'],
                        'required'          => false,
                        'multiple'          => true,
                        'expanded'          => false,
                        'attr'              => [
                            'class'         => 'form-control multiselect',
                            'data-sortable' => 'true',
                            'onchange'      => 'Mautic.checkSelectedGroupBy()',
                        ],
                    ]
                );

                // Build the filter selector
                $form->add(
                    'filters',
                    ReportFiltersType::class,
                    [
                        'entry_type'    => FilterSelectorType::class,
                        'label'         => false,
                        'entry_options' => [
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
                    CollectionType::class,
                    [
                        'entry_type'    => AggregatorType::class,
                        'label'         => false,
                        'entry_options' => [
                            'columnList' => $columns->choices,
                            'required'   => false,
                        ],
                        'allow_add'    => true,
                        'allow_delete' => true,
                        'prototype'    => true,
                        'required'     => false,
                    ]
                );

                $form->add(
                    'tableOrder',
                    CollectionType::class,
                    [
                        'entry_type'    => TableOrderType::class,
                        'label'         => false,
                        'entry_options' => [
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
                    YesNoButtonGroupType::class,
                    [
                        'label'  => false,
                        'mapped' => false,
                        'attr'   => [
                            'class' => 'filter-value',
                        ],
                        'data'    => 1,
                        'choices' => [
                            'mautic.core.form.no'      => 0,
                            'mautic.core.form.yes'     => 1,
                            'mautic.core.filter.clear' => 2,
                        ],
                    ]
                );

                $graphList = $this->reportModel->getGraphList($source);
                if (is_array($currentGraphs)) {
                    $orderColumns = array_values($currentGraphs);
                    $order        = htmlspecialchars(json_encode($orderColumns), ENT_QUOTES, 'UTF-8');
                } else {
                    $order = '[]';
                }

                $form->add(
                    'graphs',
                    ChoiceType::class,
                    [
                        'choices'           => array_flip($graphList->choices),
                        'label'             => 'mautic.report.report.form.graphs',
                        'label_attr'        => ['class' => 'control-label'],
                        'required'          => false,
                        'multiple'          => true,
                        'expanded'          => false,
                        'attr'              => [
                            'class'         => 'form-control multiselect',
                            'data-order'    => $order,
                            'data-sortable' => 'true',
                        ],
                    ]
                );
            };

            // Scheduler
            $builder->add(
                'isScheduled',
                YesNoButtonGroupType::class,
                [
                    'label'      => 'mautic.report.schedule.isScheduled',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'                => 'form-control',
                        'data-report-schedule' => 'isScheduled',
                    ],
                    'required' => false,
                ]
            );

            $builder->add(
                'toAddress',
                TextType::class,
                [
                    'label'      => 'mautic.report.schedule.toAddress.label',
                    'label_attr' => ['class' => 'control-label'],
                    'required'   => false,
                    'attr'       => [
                        'class'    => 'form-control',
                        'preaddon' => 'ri-mail-line',
                        'tooltip'  => 'mautic.report.schedule.toAddress.tooltip',
                    ],
                ]
            );

            $builder->add(
                'scheduleUnit',
                ChoiceType::class,
                [
                    'choices'           => SchedulerEnum::getUnitEnumForSelect(),
                    'expanded'          => false,
                    'multiple'          => false,
                    'label'             => 'mautic.report.schedule.every',
                    'label_attr'        => ['class' => 'control-label'],
                    'placeholder'       => false,
                    'required'          => false,
                    'attr'              => [
                        'class'                => 'form-control',
                        'data-report-schedule' => 'scheduleUnit',
                    ],
                ]
            );

            $builder->add(
                'scheduleDay',
                ChoiceType::class,
                [
                    'choices'           => SchedulerEnum::getDayEnumForSelect(),
                    'expanded'          => false,
                    'multiple'          => false,
                    'label'             => 'mautic.report.schedule.day',
                    'label_attr'        => ['class' => 'control-label'],
                    'placeholder'       => false,
                    'required'          => false,
                    'attr'              => [
                        'class'                => 'form-control',
                        'data-report-schedule' => 'scheduleDay',
                    ],
                ]
            );

            $builder->add(
                'scheduleMonthFrequency',
                ChoiceType::class,
                [
                    'choices'           => SchedulerEnum::getMonthFrequencyForSelect(),
                    'expanded'          => false,
                    'multiple'          => false,
                    'label'             => 'mautic.report.schedule.month_frequency',
                    'label_attr'        => ['class' => 'control-label'],
                    'placeholder'       => false,
                    'required'          => false,
                    'attr'              => [
                        'class'                => 'form-control',
                        'data-report-schedule' => 'scheduleMonthFrequency',
                    ],
                ]
            );

            $builder->addEventListener(
                FormEvents::PRE_SET_DATA,
                function (FormEvent $event) use ($formModifier): void {
                    $data = $event->getData();
                    $formModifier($event->getForm(), $data->getSource(), $data->getColumns(), $data->getGraphs(), $data);
                }
            );

            $builder->addEventListener(
                FormEvents::PRE_SUBMIT,
                function (FormEvent $event) use ($formModifier): void {
                    $data    = $event->getData();
                    $graphs  = $data['graphs'] ?? [];
                    $columns = $data['columns'] ?? [];
                    $source  = $data['source'] ?? null;
                    $formModifier($event->getForm(), $source, $columns, $graphs, $data);
                }
            );

            $builder->addEventListener(
                FormEvents::PRE_SUBMIT,
                function (FormEvent $event): void {
                    $data = $event->getData();

                    if (SchedulerEnum::UNIT_NOW === $data['scheduleUnit'] || SchedulerEnum::UNIT_DAILY === $data['scheduleUnit']) {
                        unset($data['scheduleDay'], $data['scheduleMonthFrequency']);
                    } elseif (SchedulerEnum::UNIT_WEEKLY === $data['scheduleUnit']) {
                        unset($data['scheduleMonthFrequency']);
                    }

                    $event->setData($data);
                }
            );

            $builder->add('buttons', FormButtonsType::class);
        }

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Report::class,
                'table_list' => [],
            ]
        );
    }

    /**
     * Extracts the keys from the table_list option and builds an array of tables for the select list.
     *
     * @param array $tables Array with the table list and columns
     */
    private function buildTableSourceList($tables): array
    {
        $temp = array_keys($tables);

        // Create an array of tables, the key is the value stored in the database and the value is what the user sees
        $list = [];

        foreach ($temp as $table) {
            $list['mautic.report.group.'.$tables[$table]['group']][$tables[$table]['display_name']] = $table;
        }

        return $list;
    }
}
