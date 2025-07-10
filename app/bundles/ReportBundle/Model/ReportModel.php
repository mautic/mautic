<?php

namespace Mautic\ReportBundle\Model;

use Doctrine\DBAL\Connections\PrimaryReadReplicaConnection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Model\GlobalSearchInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\ReportBundle\Builder\MauticReportBuilder;
use Mautic\ReportBundle\Crate\ReportDataResult;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportDataEvent;
use Mautic\ReportBundle\Event\ReportEvent;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Mautic\ReportBundle\Event\ReportQueryEvent;
use Mautic\ReportBundle\Generator\ReportGenerator;
use Mautic\ReportBundle\Helper\ReportHelper;
use Mautic\ReportBundle\ReportEvents;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Twig\Environment;

/**
 * @extends FormModel<Report>
 */
class ReportModel extends FormModel implements GlobalSearchInterface
{
    public const CHANNEL_FEATURE = 'reporting';

    /**
     * @var array
     */
    private $reportBuilderData;

    /**
     * @var mixed
     */
    protected $defaultPageLimit;

    public function __construct(
        CoreParametersHelper $coreParametersHelper,
        protected Environment $twig,
        protected ChannelListHelper $channelListHelper,
        protected FieldModel $fieldModel,
        protected ReportHelper $reportHelper,
        private CsvExporter $csvExporter,
        private ExcelExporter $excelExporter,
        EntityManagerInterface $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
        private RequestStack $requestStack,
    ) {
        $this->defaultPageLimit  = $coreParametersHelper->get('default_pagelimit');

        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    /**
     * @return \Mautic\ReportBundle\Entity\ReportRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(Report::class);
    }

    public function getPermissionBase(): string
    {
        return 'report:reports';
    }

    protected function getSession(): SessionInterface
    {
        try {
            return $this->requestStack->getSession();
        } catch (SessionNotFoundException) {
            return new Session(); // in case of CLI
        }
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof Report) {
            throw new MethodNotAllowedHttpException(['Report']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        $options = array_merge($options, [
            'table_list' => $this->getTableData('all', $entity->getSource()),
            'attr'       => [
                'readonly' => false,
            ],
        ]);

        // Fire the REPORT_ON_BUILD event off to get the table/column data

        $reportGenerator = new ReportGenerator($this->dispatcher, $this->em->getConnection(), $entity, $this->channelListHelper, $formFactory);

        return $reportGenerator->getForm($entity, $options);
    }

    public function getEntity($id = null): ?Report
    {
        if (null === $id) {
            return new Report();
        }

        return parent::getEntity($id);
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null): ?Event
    {
        if (!$entity instanceof Report) {
            throw new MethodNotAllowedHttpException(['Report']);
        }

        switch ($action) {
            case 'pre_save':
                $name = ReportEvents::REPORT_PRE_SAVE;
                break;
            case 'post_save':
                $name = ReportEvents::REPORT_POST_SAVE;
                break;
            case 'pre_delete':
                $name = ReportEvents::REPORT_PRE_DELETE;
                break;
            case 'post_delete':
                $name = ReportEvents::REPORT_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new ReportEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($event, $name);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * Build the table and graph data.
     *
     * @return mixed
     */
    public function buildAvailableReports($context, ?string $reportSource = null)
    {
        if (empty($this->reportBuilderData[$context])) {
            // Check to see if all has been obtained
            if (isset($this->reportBuilderData['all'])) {
                $this->reportBuilderData[$context]['tables'] = $this->reportBuilderData['all']['tables'][$context] ?? [];
                $this->reportBuilderData[$context]['graphs'] = $this->reportBuilderData['all']['graphs'][$context] ?? [];
            } else {
                // build them
                $eventContext = ('all' == $context) ? '' : $context;

                $event = new ReportBuilderEvent($this->translator, $this->channelListHelper, $eventContext, $this->fieldModel->getPublishedFieldArrays(), $this->reportHelper, $reportSource);
                $this->dispatcher->dispatch($event, ReportEvents::REPORT_ON_BUILD);

                $tables = $event->getTables();
                $graphs = $event->getGraphs();

                if ('all' == $context) {
                    $this->reportBuilderData[$context]['tables'] = $tables;
                    $this->reportBuilderData[$context]['graphs'] = $graphs;
                } else {
                    if (isset($tables[$context])) {
                        $this->reportBuilderData[$context]['tables'] = $tables[$context];
                    } else {
                        $this->reportBuilderData[$context]['tables'] = $tables;
                    }

                    if (isset($graphs[$context])) {
                        $this->reportBuilderData[$context]['graphs'] = $graphs[$context];
                    } else {
                        $this->reportBuilderData[$context]['graphs'] = $graphs;
                    }
                }
            }
        }

        return $this->reportBuilderData[$context];
    }

    /**
     * Builds the table lookup data for the report forms.
     *
     * @param string $context
     *
     * @return array
     */
    public function getTableData($context = 'all', ?string $reportSource = null)
    {
        $data = $this->buildAvailableReports($context, $reportSource);

        $data = (!isset($data['tables'])) ? [] : $data['tables'];

        if (array_key_exists('columns', $data)) {
            $data['columns'] = $this->preventSameAliases($data['columns']);
        }

        return $data;
    }

    /**
     * Prevent same aliases using numeric suffixes for each alias.
     */
    private function preventSameAliases(array $columns): array
    {
        $existingAliases = [];

        foreach ($columns as $key => $column) {
            $alias = $column['alias'];

            // Count suffixes
            if (!array_key_exists($alias, $existingAliases)) {
                $existingAliases[$alias] = 0;
            } else {
                ++$existingAliases[$alias];
            }

            // Add numeric suffix
            if ($existingAliases[$alias] > 0) {
                $columns[$key]['alias'] = $alias.$existingAliases[$alias];
            }
        }

        return $columns;
    }

    /**
     * @param string $context
     *
     * @return mixed
     */
    public function getGraphData($context = 'all')
    {
        $data = $this->buildAvailableReports($context);

        return (!isset($data['graphs'])) ? [] : $data['graphs'];
    }

    /**
     * @param string $context
     *
     * @return \stdClass ['choices' => [], 'choiceHtml' => '', definitions => []]
     */
    public function getColumnList($context, $isGroupBy = false): \stdClass
    {
        $tableData           = $this->getTableData($context);
        $columns             = $tableData['columns'] ?? [];
        $return              = new \stdClass();
        $return->choices     = [];
        $return->choiceHtml  = '';
        $return->definitions = [];

        foreach ($columns as $column => $data) {
            if ($isGroupBy && ('unsubscribed' == $column || 'unsubscribed_ratio' == $column || 'unique_ratio' == $column)) {
                continue;
            }
            if (isset($data['label'])) {
                $return->choiceHtml .= "<option value=\"$column\">{$data['label']}</option>\n";
                $return->choices[$column]     = $data['label'];
                $return->definitions[$column] = $data;
            }
        }

        return $return;
    }

    /**
     * @param string $context
     *
     * return \stdClass{filterList: mixed[], definitions: mixed[], operatorChoices: mixed[], operatorHtml: mixed[], filterListHtml: string}
     */
    public function getFilterList($context = 'all'): \stdClass
    {
        $tableData = $this->getTableData($context);

        $return                  = new \stdClass();
        $filters                 = $tableData['filters'] ?? $tableData['columns'] ?? [];
        $return->choices         = [];
        $return->choiceHtml      = '';
        $return->definitions     = [];
        $return->operatorHtml    = [];
        $return->operatorChoices = [];

        foreach ($filters as $filter => $data) {
            if (isset($data['label'])) {
                $return->definitions[$filter] = $data;
                $return->choices[$filter]     = $data['label'];
                $return->choiceHtml .= "<option value=\"$filter\">{$data['label']}</option>\n";

                $return->operatorChoices[$filter] = $this->getOperatorOptions($data);
                $return->operatorHtml[$filter]    = '';

                foreach ($return->operatorChoices[$filter] as $value => $label) {
                    $return->operatorHtml[$filter] .= "<option value=\"$value\">$label</option>\n";
                }
            }
        }

        return $return;
    }

    /**
     * @param string $context
     *
     * @return \stdClass ['choices' => [], choiceHtml = '']
     */
    public function getGraphList($context = 'all'): \stdClass
    {
        $graphData          = $this->getGraphData($context);
        $return             = new \stdClass();
        $return->choices    = [];
        $return->choiceHtml = '';

        // First sort
        foreach ($graphData as $key => $details) {
            $return->choices[$key] = $this->translator->trans($key).' ('.$this->translator->trans('mautic.report.graph.'.$details['type']).')';
        }
        natsort($return->choices);

        foreach ($return->choices as $key => $value) {
            $return->choiceHtml .= '<option value="'.$key.'">'.$value."</option>\n";
        }

        return $return;
    }

    /**
     * Export report.
     *
     * @param string $format
     * @param int    $page
     *
     * @return StreamedResponse|Response
     *
     * @throws \Exception
     */
    public function exportResults($format, Report $report, ReportDataResult $reportDataResult, $handle = null, $page = null)
    {
        $date = (new DateTimeHelper())->toLocalString();
        $name = str_replace(' ', '_', $date).'_'.InputHelper::alphanum($report->getName(), false, '-');

        switch ($format) {
            case 'csv':
                if (!is_null($handle)) {
                    $this->csvExporter->export($reportDataResult, $handle, $page);

                    return;
                }

                $response = new StreamedResponse(
                    function () use ($reportDataResult): void {
                        $handle = fopen('php://output', 'r+');
                        $this->csvExporter->export($reportDataResult, $handle);
                        fclose($handle);
                    }
                );

                $fileName = $name.'.csv';
                ExportResponse::setResponseHeaders($response, $fileName);

                return $response;

            case 'html':
                $content = $this->twig->render(
                    '@MauticReport/Report/export.html.twig',
                    [
                        'pageTitle'        => $name,
                        'report'           => $report,
                        'reportDataResult' => $reportDataResult,
                    ]
                );

                return new Response($content);

            case 'xlsx':
                if (!class_exists(Spreadsheet::class)) {
                    throw new \Exception('PHPSpreadsheet is required to export to Excel spreadsheets');
                }

                $response = new StreamedResponse(
                    function () use ($reportDataResult, $name): void {
                        $this->excelExporter->export($reportDataResult, $name);
                    }
                );

                $fileName = $name.'.xlsx';
                ExportResponse::setResponseHeaders($response, $fileName);

                return $response;

            default:
                return new Response();
        }
    }

    /**
     * Get report data for view rendering.
     *
     * @return mixed[]
     */
    public function getReportData(Report $entity, FormFactoryInterface $formFactory = null, array $options = []): array
    {
        // Clone dateFrom/dateTo because they handled separately in charts
        $chartDateFrom = isset($options['dateFrom']) ? clone $options['dateFrom'] : (new \DateTime('-30 days'));
        $chartDateTo   = isset($options['dateTo']) ? clone $options['dateTo'] : (new \DateTime());

        $debugData = [];

        if (isset($options['dateFrom'])) {
            // Fix date ranges if applicable
            if (!isset($options['dateTo'])) {
                $options['dateTo'] = new \DateTime();
            }

            // Adjust dateTo to be end of day or to current hour if today
            $now = new \DateTime();
            if ($now->format('Y-m-d') == $options['dateTo']->format('Y-m-d')) {
                $options['dateTo'] = $now;
            } else {
                $options['dateTo']->setTime(23, 59, 59);
            }

            // Convert date ranges to UTC for fetching tabular data
            $options['dateFrom']->setTimeZone(new \DateTimeZone('UTC'));
            $options['dateTo']->setTimeZone(new \DateTimeZone('UTC'));
        }

        $paginate        = !empty($options['paginate']);
        $reportPage      = $options['reportPage'] ?? 1;
        $data            = $graphs            = [];
        $reportGenerator = new ReportGenerator($this->dispatcher, $this->getConnection(), $entity, $this->channelListHelper, $formFactory);

        $selectedColumns = $entity->getColumns();
        $totalResults    = $limit    = 0;

        // Prepare the query builder
        $tableDetails      = $this->getTableData($entity->getSource());
        $dataColumns       = $dataAggregatorColumns = [];
        $aggregatorColumns = ($aggregators = $entity->getAggregators()) ? $aggregators : [];

        foreach ($aggregatorColumns as $aggregatorColumn) {
            $selectedColumns[] = $aggregatorColumn['column'];
            // add aggregator columns to dataColumns also
            $dataColumns[$aggregatorColumn['function'].' '.$aggregatorColumn['column']]           = $aggregatorColumn['column'];
            $dataAggregatorColumns[$aggregatorColumn['function'].' '.$aggregatorColumn['column']] = $aggregatorColumn['column'];
        }
        // Build a reference for column to data column (without table prefix)
        foreach ($tableDetails['columns'] as $dbColumn => &$columnData) {
            $dataColumns[$columnData['alias']] = $dbColumn;
        }

        $session    = $this->getSession();
        $orderBy    = '';
        $orderByDir = 'ASC';
        // make sure to use the session if it's started. Otherwise this is impossible to test:
        // Failed to start the session because headers have already been sent by "/var/www/html/vendor/phpunit/phpunit/src/Util/Printer.php" at line 104.
        if ($session->isStarted()) {
            $orderBy    = $session->get('mautic.report.'.$entity->getId().'.orderby', $orderBy);
            $orderByDir = $session->get('mautic.report.'.$entity->getId().'.orderbydir', $orderByDir);
        }

        $dataOptions = [
            'order'          => (!empty($orderBy)) ? [$orderBy, $orderByDir] : false,
            'columns'        => $tableDetails['columns'],
            'filters'        => $tableDetails['filters'] ?? $tableDetails['columns'],
            'dateFrom'       => $options['dateFrom'] ?? null,
            'dateTo'         => $options['dateTo'] ?? null,
            'dynamicFilters' => $options['dynamicFilters'] ?? [],
        ];

        /** @var QueryBuilder $query */
        $query                 = $reportGenerator->getQuery($dataOptions);
        $options['translator'] = $this->translator;

        $contentTemplate = $reportGenerator->getContentTemplate();

        // set what page currently on so that we can return here after form submission/cancellation
        $session = $this->getSession();
        if ($session->isStarted()) {
            $session->set('mautic.report.'.$entity->getId().'.page', $reportPage);
        }

        // Reset the orderBy as it causes errors in graphs and the count query in table data
        $parts = $query->getQueryParts();
        $order = $parts['orderBy'];
        $query->resetQueryPart('orderBy');

        if (empty($options['ignoreGraphData'])) {
            $chartQuery            = new ChartQuery($this->em->getConnection(), $chartDateFrom, $chartDateTo);
            $options['chartQuery'] = $chartQuery;

            // Check to see if this is an update from AJAX
            $selectedGraphs = (!empty($options['graphName'])) ? [$options['graphName']] : $entity->getGraphs();
            if (!empty($selectedGraphs)) {
                $availableGraphs = $this->getGraphData($entity->getSource());
                if (empty($query)) {
                    $query = $reportGenerator->getQuery();
                }

                $eventGraphs                     = [];
                $defaultGraphOptions             = $options;
                $defaultGraphOptions['dateFrom'] = $chartDateFrom;
                $defaultGraphOptions['dateTo']   = $chartDateTo;

                foreach ($selectedGraphs as $g) {
                    if (isset($availableGraphs[$g])) {
                        $graphOptions    = $availableGraphs[$g]['options'] ?? [];
                        $graphOptions    = array_merge($defaultGraphOptions, $graphOptions);
                        $eventGraphs[$g] = [
                            'options' => $graphOptions,
                            'type'    => $availableGraphs[$g]['type'],
                        ];
                    }
                }

                $event = new ReportGraphEvent($entity, $eventGraphs, $query);
                $this->dispatcher->dispatch($event, ReportEvents::REPORT_ON_GRAPH_GENERATE);
                $graphs = $event->getGraphs();

                unset($defaultGraphOptions);
            }
        }

        $columnsAllowed = $this->getColumnList($entity->getSource());
        $order          = $this->getOrderBySanitized($order, $columnsAllowed);
        if ($order['hasOrderBy']) {
            $query->add('orderBy', $order['orderBy']);
        }

        // Allow plugin to manipulate the query
        $event = new ReportQueryEvent($entity, $query, $totalResults, $dataOptions);
        $this->dispatcher->dispatch($event, ReportEvents::REPORT_QUERY_PRE_EXECUTE);
        $query = $event->getQuery();

        if (empty($options['ignoreTableData']) && !empty($selectedColumns)) {
            if ($paginate) {
                // Build the options array to pass into the query
                if ($session->isStarted()) {
                    $limit = $session->get('mautic.report.'.$entity->getId().'.limit', $this->defaultPageLimit);
                }
                if (!empty($options['limit'])) {
                    $limit      = $options['limit'];
                    $reportPage = $options['page'];
                }
                $start = (1 === $reportPage) ? 0 : (($reportPage - 1) * $limit);
                if ($start < 0) {
                    $start = 0;
                }

                if (empty($options['totalResults'])) {
                    $options['totalResults'] = $totalResults = $this->getTotalCount($query, $debugData);
                } else {
                    $totalResults = $options['totalResults'];
                }

                if ($limit > 0) {
                    $query->setFirstResult($start)
                        ->setMaxResults($limit);
                }
            }

            $queryTime = microtime(true);
            $data      = $query->executeQuery()->fetchAllAssociative();
            $queryTime = round((microtime(true) - $queryTime) * 1000);

            if ($queryTime >= 1000) {
                $queryTime *= 1000;

                $queryTime .= 's';
            } else {
                $queryTime .= 'ms';
            }

            if (!$paginate) {
                $totalResults = count($data);
            }

            // Allow plugin to manipulate the data
            $event = new ReportDataEvent($entity, $data, $totalResults, $dataOptions);
            $this->dispatcher->dispatch($event, ReportEvents::REPORT_ON_DISPLAY);
            $data        = $event->getData();
            $dataOptions = $event->getOptions();
        }

        if ($this->isDebugMode()) {
            $debugData['query'] = $query->getSQL();
            $params             = $query->getParameters();

            foreach ($params as $name => $param) {
                if (is_array($param)) {
                    $param = implode("','", $param);
                }
                $debugData['query'] = str_replace(":$name", "'$param'", $debugData['query']);
            }

            $debugData['query_time'] = $queryTime ?? 'N/A';
        }

        foreach ($data as $keys => $lead) {
            foreach ($lead as $key => $field) {
                $data[$keys][$key] = html_entity_decode((string) $field, ENT_QUOTES);
            }
        }

        return [
            'totalResults'      => $totalResults,
            'data'              => $data,
            'dataColumns'       => $dataColumns,
            'graphs'            => $graphs,
            'contentTemplate'   => $contentTemplate,
            'columns'           => $dataOptions['columns'],
            'limit'             => ($paginate) ? $limit : 0,
            'page'              => ($paginate) ? $reportPage : 1,
            'dateFrom'          => $dataOptions['dateFrom'],
            'dateTo'            => $dataOptions['dateTo'],
            'debug'             => $debugData,
            'aggregatorColumns' => $dataAggregatorColumns,
        ];
    }

    /**
     * Sanitize order by array comparing it to the allowed columns.
     *
     * @param iterable<mixed> $orderBys
     *
     * @return iterable<mixed>
     */
    private function getOrderBySanitized(iterable $orderBys, \stdClass $allowedColumns): iterable
    {
        $hasOrderBy = false;
        foreach ($orderBys as $key => $orderBy) {
            if ($this->orderByIsValid($orderBy, $allowedColumns->choices)) {
                $hasOrderBy = true;
                continue;
            }
            $orderBys[$key] = '';
        }

        return [
            'orderBy'    => $orderBys,
            'hasOrderBy' => $hasOrderBy,
        ];
    }

    /**
     * Check if order by is valid.
     *
     * @param array<string, string> $allowedColumns
     */
    private function orderByIsValid(string $order, array $allowedColumns): bool
    {
        if (empty($order)) {
            return false;
        }

        $orderBy         = $order;
        $oderByDirection = '';

        if (str_contains($order, ' ')) {
            $orderTemp       = explode(' ', $order);
            $orderBy         = $orderTemp[0];
            $oderByDirection = $orderTemp[1];
        }

        if (!array_key_exists($orderBy, $allowedColumns) || !in_array($oderByDirection, ['ASC', 'DESC', ''])) {
            return false;
        }

        return true;
    }

    /**
     * @return mixed[]
     */
    public function getReportsWithGraphs(): array
    {
        $ownedBy = $this->security->isGranted('report:reports:viewother') ? null : $this->userHelper->getUser()->getId();

        return $this->getRepository()->findReportsWithGraphs($ownedBy);
    }

    /**
     * Determine what operators should be used for the filter type.
     *
     * @return mixed|string
     */
    private function getOperatorOptions(array $data)
    {
        if (isset($data['operators'])) {
            // Custom operators
            $options = $data['operators'];
        } else {
            $operator = $data['operatorGroup'] ?? $data['type'];

            if (!array_key_exists($operator, MauticReportBuilder::OPERATORS)) {
                $operator = 'default';
            }

            $options = MauticReportBuilder::OPERATORS[$operator];
        }

        foreach ($options as &$label) {
            $label = $this->translator->trans($label);
        }

        return $options;
    }

    private function getTotalCount(QueryBuilder $qb, array &$debugData): int
    {
        $countQb = clone $qb;
        $countQb->resetQueryParts();

        $countQb->select('count(*)')
            ->from('('.$qb->getSQL().')', 'c');

        if ($this->isDebugMode()) {
            $debugData['count_query'] = $countQb->getSQL();
        }

        return (int) $countQb->executeQuery()->fetchOne();
    }

    /**
     * @param int $segmentId
     */
    public function getReportsIdsWithDependenciesOnSegment($segmentId): array
    {
        $search = 'lll.leadlist_id';
        $filter = [
            'force'  => [
                ['column' => 'r.filters', 'expr' => 'LIKE', 'value'=>'%'.$search.'"%'],
            ],
        ];
        $entities = $this->getEntities(
            [
                'filter'     => $filter,
            ]
        );
        $dependents = [];
        foreach ($entities as $entity) {
            $retrFilters = $entity->getFilters();
            foreach ($retrFilters as $eachFilter) {
                if ($eachFilter['column'] == $search && $eachFilter['value'] == $segmentId) {
                    $dependents[] = $entity->getId();
                }
            }
        }

        return $dependents;
    }

    /**
     * @return array<int, int>
     */
    public function getReportsIdsWithDependenciesOnEmail(int $emailId): array
    {
        $search = 'e.id';
        $filter = [
            'force'  => [
                ['column' => 'r.source', 'expr' => 'IN', 'value'=> ['emails', 'email.stats']],
                ['column' => 'r.filters', 'expr' => 'LIKE', 'value'=>'%'.$search.'"%'],
            ],
        ];
        $entities = $this->getEntities(
            [
                'filter'     => $filter,
            ]
        );

        $dependents = [];
        foreach ($entities as $entity) {
            foreach ($entity->getFilters() as $entityFilter) {
                if ($entityFilter['column'] == $search && $entityFilter['value'] == $emailId) {
                    $dependents[] = $entity->getId();
                }
            }
        }

        return array_unique($dependents);
    }

    /**
     * @return array<int, int>
     */
    public function getReportsIdsWithDependenciesOnTag(int $tagId): array
    {
        $search = 'tag';
        $filter = [
            'force'  => [
                ['column' => 'r.filters', 'expr' => 'LIKE', 'value'=>'%'.$search.'"%'],
            ],
        ];
        $entities = $this->getEntities(
            [
                'filter'     => $filter,
            ]
        );

        $dependents = [];
        foreach ($entities as $entity) {
            foreach ($entity->getFilters() as $entityFilter) {
                if ($entityFilter['column'] == $search && (is_array($entityFilter['value']) && in_array($tagId, $entityFilter['value']))) {
                    $dependents[] = $entity->getId();
                }
            }
        }

        return array_unique($dependents);
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    private function getConnection()
    {
        $connection = $this->em->getConnection();
        if ($connection instanceof PrimaryReadReplicaConnection) {
            $connection->ensureConnectedToReplica();
        }

        return $connection;
    }

    protected function isDebugMode(): bool
    {
        return MAUTIC_ENV == 'dev' || $this->coreParametersHelper->get('debug');
    }
}
