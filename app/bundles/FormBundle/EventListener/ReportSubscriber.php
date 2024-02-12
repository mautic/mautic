<?php

namespace Mautic\FormBundle\EventListener;

use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\SubmissionRepository;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Mautic\ReportBundle\Helper\ReportHelper;
use Mautic\ReportBundle\ReportEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ReportSubscriber implements EventSubscriberInterface
{
    public const CONTEXT_FORMS           = 'forms';

    public const CONTEXT_FORM_SUBMISSION = 'form.submissions';

    public const CONTEXT_FORM_RESULT     = 'form.results';

    public function __construct(
        private CompanyReportData $companyReportData,
        private SubmissionRepository $submissionRepository,
        private FormModel $formModel,
        private ReportHelper $reportHelper,
        private CoreParametersHelper $coreParametersHelper,
        private TranslatorInterface $translator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ReportEvents::REPORT_ON_BUILD          => ['onReportBuilder', 0],
            ReportEvents::REPORT_ON_GENERATE       => ['onReportGenerate', 0],
            ReportEvents::REPORT_ON_GRAPH_GENERATE => ['onReportGraphGenerate', 0],
        ];
    }

    /**
     * Add available tables and columns to the report builder lookup.
     */
    public function onReportBuilder(ReportBuilderEvent $event): void
    {
        if (!$event->checkContext([self::CONTEXT_FORMS, self::CONTEXT_FORM_SUBMISSION, self::CONTEXT_FORM_RESULT])) {
            return;
        }

        // Forms
        $prefix  = 'f.';
        $columns = [
            $prefix.'alias' => [
                'label' => 'mautic.core.alias',
                'type'  => 'string',
            ],
        ];
        $columns = array_merge(
            $columns,
            $event->getStandardColumns($prefix, [], 'mautic_form_action'),
            $event->getCategoryColumns()
        );
        $data = [
            'display_name' => 'mautic.form.forms',
            'columns'      => $columns,
        ];
        $event->addTable(self::CONTEXT_FORMS, $data);

        if ($event->checkContext(self::CONTEXT_FORM_SUBMISSION)) {
            // Form submissions
            $submissionPrefix  = 'fs.';
            $pagePrefix        = 'p.';
            $submissionColumns = [
                $submissionPrefix.'date_submitted' => [
                    'label'          => 'mautic.form.report.submit.date_submitted',
                    'type'           => 'datetime',
                    'groupByFormula' => 'DATE('.$submissionPrefix.'date_submitted)',
                ],
                $submissionPrefix.'referer' => [
                    'label' => 'mautic.core.referer',
                    'type'  => 'string',
                ],
                $pagePrefix.'id' => [
                    'label' => 'mautic.form.report.page_id',
                    'type'  => 'int',
                    'link'  => 'mautic_page_action',
                ],
                $pagePrefix.'title' => [
                    'label' => 'mautic.form.report.page_name',
                    'type'  => 'string',
                ],
            ];

            $companyColumns = $this->companyReportData->getCompanyData();

            $formSubmissionColumns = array_merge(
                $submissionColumns,
                $columns,
                $event->getCampaignByChannelColumns(),
                $event->getLeadColumns(),
                $event->getIpColumn(),
                $companyColumns
            );

            $data = [
                'display_name' => 'mautic.form.report.submission.table',
                'columns'      => $formSubmissionColumns,
            ];
            $event->addTable(self::CONTEXT_FORM_SUBMISSION, $data, self::CONTEXT_FORMS);

            // Register graphs
            $context = self::CONTEXT_FORM_SUBMISSION;
            $event->addGraph($context, 'line', 'mautic.form.graph.line.submissions');
            $event->addGraph($context, 'table', 'mautic.form.table.top.referrers');
            $event->addGraph($context, 'table', 'mautic.form.table.most.submitted');
        }

        if ($event->checkContext(self::CONTEXT_FORM_RESULT)) {
            $formRepository = $this->formModel->getRepository();
            // select only the table for an existing report, if the setting is disabled
            if (false === $this->coreParametersHelper->get('form_results_data_sources')) {
                $reportSource = empty($event->getContext()) ? ($event->getReportSource() ?? '') : $event->getContext();

                $id   = $formRepository->getFormTableIdViaResults($reportSource);
                $args = [
                    'filter' => [
                        'force' => [
                            [
                                'column' => 'f.id',
                                'expr'   => 'eq',
                                'value'  => $id,
                            ],
                        ],
                    ],
                ];
            }

            $forms = $formRepository->getEntities($args ?? []);
            foreach ($forms as $form) {
                $formEntity         = $form[0];

                $formResultsColumns = $this->getFormResultsColumns($formEntity);
                $mappedFieldValues  = $formEntity->getMappedFieldValues();
                $columnsMapped      = [];
                foreach ($mappedFieldValues as $item) {
                    $columns        = $this->reportHelper->getMappedObjectColumns($item['mappedObject'], $item);
                    $columnsMapped  = array_merge($columnsMapped, $columns);
                }

                $formResultsColumns = array_merge($formResultsColumns, $columnsMapped);
                $data               = [
                    'display_name' => $formEntity->getId().' '.$formEntity->getName(),
                    'columns'      => $formResultsColumns,
                ];

                $resultsTableName   = $formRepository->getResultsTableName($formEntity->getId(), $formEntity->getAlias());
                $event->addTable(self::CONTEXT_FORM_RESULT.'.'.$resultsTableName, $data, self::CONTEXT_FORM_RESULT);
            }
        }
    }

    /**
     * Initialize the QueryBuilder object to generate reports from.
     */
    public function onReportGenerate(ReportGeneratorEvent $event): void
    {
        if (!$event->checkContext([self::CONTEXT_FORMS, self::CONTEXT_FORM_SUBMISSION, self::CONTEXT_FORM_RESULT])) {
            return;
        }

        $context = $event->getContext();
        $qb      = $event->getQueryBuilder();

        switch ($context) {
            case self::CONTEXT_FORMS:
                $qb->from(MAUTIC_TABLE_PREFIX.'forms', 'f');
                $event->addCategoryLeftJoin($qb, 'f');
                break;
            case self::CONTEXT_FORM_SUBMISSION:
                $event->applyDateFilters($qb, 'date_submitted', 'fs');

                $qb->from(MAUTIC_TABLE_PREFIX.'form_submissions', 'fs')
                    ->leftJoin('fs', MAUTIC_TABLE_PREFIX.'forms', 'f', 'f.id = fs.form_id')
                    ->leftJoin('fs', MAUTIC_TABLE_PREFIX.'pages', 'p', 'p.id = fs.page_id');
                $event->addCategoryLeftJoin($qb, 'f');
                $event->addLeadLeftJoin($qb, 'fs');
                $event->addIpAddressLeftJoin($qb, 'fs');
                $event->addCampaignByChannelJoin($qb, 'f', 'form');

                if ($this->companyReportData->eventHasCompanyColumns($event)) {
                    $event->addCompanyLeftJoin($qb);
                }

                break;
            case self::CONTEXT_FORM_RESULT.str_replace(self::CONTEXT_FORM_RESULT, '', $context):
                $resultsTableName = str_replace(self::CONTEXT_FORM_RESULT.'.', '', $context);

                $qb->from(MAUTIC_TABLE_PREFIX.$resultsTableName, 'fr')
                    ->leftJoin('fr', MAUTIC_TABLE_PREFIX.'form_submissions', 'fs', 'fs.id = fr.submission_id');
                $event->addLeadLeftJoin($qb, 'fs');
                if ($this->companyReportData->eventHasCompanyColumns($event)) {
                    $event->addCompanyLeftJoin($qb);
                }

                break;
        }

        $event->setQueryBuilder($qb);
    }

    /**
     * Initialize the QueryBuilder object to generate reports from.
     */
    public function onReportGraphGenerate(ReportGraphEvent $event): void
    {
        // Context check, we only want to fire for Lead reports
        if (!$event->checkContext(self::CONTEXT_FORM_SUBMISSION)) {
            return;
        }

        $graphs = $event->getRequestedGraphs();
        $qb     = $event->getQueryBuilder();

        foreach ($graphs as $g) {
            $options      = $event->getOptions($g);
            $queryBuilder = clone $qb;
            $chartQuery   = clone $options['chartQuery'];
            $chartQuery->applyDateFilters($queryBuilder, 'date_submitted', 'fs');

            switch ($g) {
                case 'mautic.form.graph.line.submissions':
                    $chart = new LineChart(null, $options['dateFrom'], $options['dateTo']);
                    $chartQuery->modifyTimeDataQuery($queryBuilder, 'date_submitted', 'fs');
                    $hits = $chartQuery->loadAndBuildTimeData($queryBuilder);
                    $chart->setDataset($options['translator']->trans($g), $hits);
                    $data         = $chart->render();
                    $data['name'] = $g;

                    $event->setGraph($g, $data);
                    break;

                case 'mautic.form.table.top.referrers':
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $this->submissionRepository->getTopReferrers($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-sign-in';
                    $graphData['link']      = 'mautic_form_action';
                    $event->setGraph($g, $graphData);
                    break;

                case 'mautic.form.table.most.submitted':
                    $limit                  = 10;
                    $offset                 = 0;
                    $items                  = $this->submissionRepository->getMostSubmitted($queryBuilder, $limit, $offset);
                    $graphData              = [];
                    $graphData['data']      = $items;
                    $graphData['name']      = $g;
                    $graphData['iconClass'] = 'fa-check-square-o';
                    $graphData['link']      = 'mautic_form_action';
                    $event->setGraph($g, $graphData);
                    break;
            }
            unset($queryBuilder);
        }
    }

    /**
     * Get form fields and create the list of the form results table columns.
     *
     * @return array<string, array<string, string>>
     */
    private function getFormResultsColumns(Form $form): array
    {
        $prefix         = 'fr.';
        $fields         = $form->getFields();
        $viewOnlyFields = $this->formModel->getCustomComponents()['viewOnlyFields'];

        foreach ($fields as $field) {
            if (!in_array($field->getType(), $viewOnlyFields)) {
                $index                      = $prefix.$field->getAlias();
                $formResultsColumns[$index] = [
                    'label' => $this->translator->trans('mautic.form.report.form_results.label', ['%field%' => $field->getLabel()]),
                    'type'  => 'number' === $field->getType() ? 'int' : 'string',
                    'alias' => $field->getAlias(),
                ];

                if ('file' === $field->getType()) {
                    $formResultsColumns[$index]['link']           = 'mautic_form_file_download_by_name';
                    $formResultsColumns[$index]['linkParameters'] = [
                        'fieldId'  => $field->getId(),
                        'fileName' => '%alias%',
                    ];
                }
            }
        }

        $formResultsColumns[$prefix.'submission_id'] = [
            'label' => $this->translator->trans('mautic.form.report.form_results.label', ['%field%' => $this->translator->trans('mautic.form.report.submission.id')]),
            'type'  => 'int',
            'alias' => 'submissionId',
        ];
        $formResultsColumns[$prefix.'form_id']       = [
            'label' => $this->translator->trans('mautic.form.report.form_results.label', ['%field%' => $this->translator->trans('mautic.form.report.form_id')]),
            'type'  => 'int',
            'link'  => 'mautic_form_action',
            'alias' => 'formResultId',
        ];

        return $formResultsColumns;
    }
}
