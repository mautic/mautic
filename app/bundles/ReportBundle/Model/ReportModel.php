<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Model;

use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Templating\Helper\FormatterHelper;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\ReportBundle\Builder\MauticReportBuilder;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportDataEvent;
use Mautic\ReportBundle\Event\ReportEvent;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Mautic\ReportBundle\Generator\ReportGenerator;
use Mautic\ReportBundle\Helper\ReportHelper;
use Mautic\ReportBundle\ReportEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class ReportModel.
 */
class ReportModel extends FormModel
{
    const CHANNEL_FEATURE = 'reporting';

    /**
     * @var mixed
     */
    protected $defaultPageLimit;

    /**
     * @var FormatterHelper
     */
    protected $formatterHelper;

    /**
     * @var TemplatingHelper
     */
    protected $templatingHelper;

    /**
     * @var ChannelListHelper
     */
    protected $channelListHelper;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var FieldModel
     */
    protected $fieldModel;

    /**
     * @var ReportHelper
     */
    protected $reportHelper;

    /**
     * ReportModel constructor.
     *
     * @param CoreParametersHelper $coreParametersHelper
     * @param FormatterHelper      $formatterHelper
     * @param TemplatingHelper     $templatingHelper
     * @param ChannelListHelper    $channelListHelper
     */
    public function __construct(
        CoreParametersHelper $coreParametersHelper,
        FormatterHelper $formatterHelper,
        TemplatingHelper $templatingHelper,
        ChannelListHelper $channelListHelper,
        FieldModel $fieldModel,
        ReportHelper $reportHelper
    ) {
        $this->defaultPageLimit  = $coreParametersHelper->getParameter('default_pagelimit');
        $this->formatterHelper   = $formatterHelper;
        $this->templatingHelper  = $templatingHelper;
        $this->channelListHelper = $channelListHelper;
        $this->fieldModel        = $fieldModel;
        $this->reportHelper      = $reportHelper;
    }

    /**
     * @param Session $session
     */
    public function setSession(Session $session)
    {
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\ReportBundle\Entity\ReportRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticReportBundle:Report');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'report:reports';
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof Report) {
            throw new MethodNotAllowedHttpException(['Report']);
        }

        $params              = (!empty($action)) ? ['action' => $action] : [];
        $params['read_only'] = false;

        // Fire the REPORT_ON_BUILD event off to get the table/column data

        $params['table_list'] = $this->getTableData();

        $reportGenerator = new ReportGenerator($this->dispatcher, $this->em->getConnection(), $entity, $this->channelListHelper, $formFactory);

        return $reportGenerator->getForm($entity, $params);
    }

    /**
     * {@inheritdoc}
     *
     * @return Report|null
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new Report();
        }

        return parent::getEntity($id);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
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

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * Build the table and graph data.
     *
     * @param $context
     *
     * @return mixed
     */
    public function buildAvailableReports($context)
    {
        static $data = [];

        if (empty($data[$context])) {
            // Check to see if all has been obtained
            if (isset($data['all'])) {
                $data[$context]['tables'] = &$data['all']['tables'][$context];
                $data[$context]['graphs'] = &$data['all']['graphs'][$context];
            } else {
                //build them
                $eventContext = ($context == 'all') ? '' : $context;

                $event = new ReportBuilderEvent($this->translator, $this->channelListHelper, $eventContext, $this->fieldModel->getPublishedFieldArrays(), $this->reportHelper);
                $this->dispatcher->dispatch(ReportEvents::REPORT_ON_BUILD, $event);

                $tables = $event->getTables();
                $graphs = $event->getGraphs();

                if ($context == 'all') {
                    $data[$context]['tables'] = $tables;
                    $data[$context]['graphs'] = $graphs;
                } else {
                    if (isset($tables[$context])) {
                        $data[$context]['tables'] = $tables[$context];
                    } else {
                        $data[$context]['tables'] = $tables;
                    }

                    if (isset($graphs[$context])) {
                        $data[$context]['graphs'] = $graphs[$context];
                    } else {
                        $data[$context]['graphs'] = $graphs;
                    }
                }
            }
        }

        return $data[$context];
    }

    /**
     * Builds the table lookup data for the report forms.
     *
     * @param string $context
     *
     * @return array
     */
    public function getTableData($context = 'all')
    {
        $data = $this->buildAvailableReports($context);

        return (!isset($data['tables'])) ? [] : $data['tables'];
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
    public function getColumnList($context)
    {
        $tableData           = $this->getTableData($context);
        $columns             = isset($tableData['columns']) ? $tableData['columns'] : [];
        $return              = new \stdClass();
        $return->choices     = [];
        $return->choiceHtml  = '';
        $return->definitions = [];

        foreach ($columns as $column => $data) {
            if (isset($data['label'])) {
                $return->choiceHtml .= "<option value=\"$column\">{$data['label']}</option>\n";
                $return->choices[$column]     = $data['label'];
                $return->definitions[$column] = $data;
            }
        }

        return $return;
    }

    /**
     * @property filterList
     * @property definitions
     *
     * @param string $context
     *
     * @return \stdClass [filterList => [], definitions => [], operatorChoices =>  [], operatorHtml => [], filterListHtml => '']
     */
    public function getFilterList($context = 'all')
    {
        $tableData = $this->getTableData($context);

        $return  = new \stdClass();
        $filters = (isset($tableData['filters'])) ? $tableData['filters']
            : (isset($tableData['columns']) ? $tableData['columns'] : []);
        $return->choices         = [];
        $return->choiceHtml      = '';
        $return->definitions     = [];
        $return->operatorHtml    = [];
        $return->operatorChoices = [];

        foreach ($filters as $filter => $data) {
            if (isset($data['label'])) {
                $return->definitions[$filter] = $data;
                $return->choices [$filter]    = $data['label'];
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
    public function getGraphList($context = 'all')
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
     * @param $format
     * @param $report
     * @param $reportData
     *
     * @return StreamedResponse|Response
     *
     * @throws \Exception
     */
    public function exportResults($format, $report, $reportData)
    {
        $formatter = $this->formatterHelper;
        $date      = (new DateTimeHelper())->toLocalString();
        $name      = str_replace(' ', '_', $date).'_'.InputHelper::alphanum($report->getName(), false, '-');

        switch ($format) {
            case 'csv':
                $response = new StreamedResponse(
                    function () use ($reportData, $report, $formatter) {
                        $handle = fopen('php://output', 'r+');
                        $header = [];

                        //build the data rows
                        foreach ($reportData['data'] as $count => $data) {
                            $row = [];
                            foreach ($data as $k => $v) {
                                if ($count === 0) {
                                    //set the header
                                    $header[] = $k;
                                }

                                $row[] = $formatter->_($v, $reportData['columns'][$reportData['dataColumns'][$k]]['type'], true);
                            }

                            if ($count === 0) {
                                //write the column names row
                                fputcsv($handle, $header);
                            }

                            //write the row
                            fputcsv($handle, $row);

                            //free memory
                            unset($row, $reportData['data'][$count]);
                        }

                        fclose($handle);
                    }
                );

                $response->headers->set('Content-Type', 'application/force-download');
                $response->headers->set('Content-Type', 'application/octet-stream');
                $response->headers->set('Content-Disposition', 'attachment; filename="'.$name.'.csv"');
                $response->headers->set('Expires', 0);
                $response->headers->set('Cache-Control', 'must-revalidate');
                $response->headers->set('Pragma', 'public');

                return $response;
            case 'html':
                $content = $this->templatingHelper->getTemplating()->renderResponse(
                    'MauticReportBundle:Report:export.html.php',
                    [
                        'data'      => $reportData['data'],
                        'columns'   => $reportData['columns'],
                        'pageTitle' => $name,
                        'graphs'    => $reportData['graphs'],
                        'report'    => $report,
                        'dateFrom'  => $reportData['dateFrom'],
                        'dateTo'    => $reportData['dateTo'],
                    ]
                )->getContent();

                return new Response($content);
            case 'xlsx':
                if (class_exists('PHPExcel')) {
                    $response = new StreamedResponse(
                        function () use ($formatter, $reportData, $report, $name) {
                            $objPHPExcel = new \PHPExcel();
                            $objPHPExcel->getProperties()->setTitle($name);

                            $objPHPExcel->createSheet();
                            $header = [];

                            //build the data rows
                            foreach ($reportData['data'] as $count => $data) {
                                $row = [];
                                foreach ($data as $k => $v) {
                                    if ($count === 0) {
                                        //set the header
                                        $header[] = $k;
                                    }
                                    $row[] = $formatter->_($v, $reportData['columns'][$reportData['dataColumns'][$k]]['type'], true);
                                }

                                if ($count === 0) {
                                    //write the column names row
                                    $objPHPExcel->getActiveSheet()->fromArray($header, null, 'A1');
                                }

                                //write the row
                                $rowCount = $count + 2;
                                $objPHPExcel->getActiveSheet()->fromArray($row, null, "A{$rowCount}");

                                //free memory
                                unset($row, $reportData['data'][$count]);
                            }

                            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
                            $objWriter->setPreCalculateFormulas(false);

                            $objWriter->save('php://output');
                        }
                    );

                    $response->headers->set('Content-Type', 'application/force-download');
                    $response->headers->set('Content-Type', 'application/octet-stream');
                    $response->headers->set('Content-Disposition', 'attachment; filename="'.$name.'.xlsx"');
                    $response->headers->set('Expires', 0);
                    $response->headers->set('Cache-Control', 'must-revalidate');
                    $response->headers->set('Pragma', 'public');

                    return $response;
                }
                throw new \Exception('PHPExcel is required to export to Excel spreadsheets');
            default:
                return new Response();
        }
    }

    /**
     * Get report data for view rendering.
     *
     * @param       $entity
     * @param       $formFactory
     * @param array $options
     *
     * @return array
     */
    public function getReportData($entity, $formFactory = null, $options = [])
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

            // Fix the time frames
            if ($options['dateFrom'] == $options['dateTo']) {
                $options['dateTo']->modify('+1 day');
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
        $reportPage      = (isset($options['reportPage'])) ? $options['reportPage'] : 1;
        $data            = $graphs            = [];
        $reportGenerator = new ReportGenerator($this->dispatcher, $this->em->getConnection(), $entity, $this->channelListHelper, $formFactory);

        $selectedColumns = $entity->getColumns();
        $totalResults    = $limit    = 0;

        // Prepare the query builder
        $tableDetails = $this->getTableData($entity->getSource());

        // Build a reference for column to data column (without table prefix)
        $dataColumns = [];
        foreach ($tableDetails['columns'] as $dbColumn => &$columnData) {
            $dataColumns[$columnData['alias']] = $dbColumn;
        }

        $orderBy    = $this->session->get('mautic.report.'.$entity->getId().'.orderby', '');
        $orderByDir = $this->session->get('mautic.report.'.$entity->getId().'.orderbydir', 'ASC');

        $dataOptions = [
            'order'          => (!empty($orderBy)) ? [$orderBy, $orderByDir] : false,
            'columns'        => $tableDetails['columns'],
            'filters'        => (isset($tableDetails['filters'])) ? $tableDetails['filters'] : $tableDetails['columns'],
            'dateFrom'       => (isset($options['dateFrom'])) ? $options['dateFrom'] : null,
            'dateTo'         => (isset($options['dateTo'])) ? $options['dateTo'] : null,
            'dynamicFilters' => (isset($options['dynamicFilters'])) ? $options['dynamicFilters'] : [],
        ];

        /** @var \Doctrine\DBAL\Query\QueryBuilder $query */
        $query                 = $reportGenerator->getQuery($dataOptions);
        $options['translator'] = $this->translator;

        $contentTemplate = $reportGenerator->getContentTemplate();

        //set what page currently on so that we can return here after form submission/cancellation
        $this->session->set('mautic.report.'.$entity->getId().'.page', $reportPage);

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
                        $graphOptions    = isset($availableGraphs[$g]['options']) ? $availableGraphs[$g]['options'] : [];
                        $graphOptions    = array_merge($defaultGraphOptions, $graphOptions);
                        $eventGraphs[$g] = [
                            'options' => $graphOptions,
                            'type'    => $availableGraphs[$g]['type'],
                        ];
                    }
                }

                $event = new ReportGraphEvent($entity, $eventGraphs, $query);
                $this->dispatcher->dispatch(ReportEvents::REPORT_ON_GRAPH_GENERATE, $event);
                $graphs = $event->getGraphs();

                unset($defaultGraphOptions);
            }
        }

        if (empty($options['ignoreTableData']) && !empty($selectedColumns)) {
            if ($paginate) {
                // Build the options array to pass into the query
                $limit = $this->session->get('mautic.report.'.$entity->getId().'.limit', $this->defaultPageLimit);
                $start = ($reportPage === 1) ? 0 : (($reportPage - 1) * $limit);
                if ($start < 0) {
                    $start = 0;
                }

                // Must make two queries here, one to get count and one to select data
                $select = $parts['select'];

                // Get the count
                $query->select('COUNT(*) as count');

                $result       = $query->execute()->fetchAll();
                $totalResults = (!empty($result[0]['count'])) ? $result[0]['count'] : 0;

                // Set the limit and get the results
                if ($limit > 0) {
                    $query->setFirstResult($start)
                        ->setMaxResults($limit);
                }

                $query->select($select);
                $query->add('orderBy', $order);
            }

            $queryTime = microtime(true);
            $data      = $query->execute()->fetchAll();
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
            $this->dispatcher->dispatch(ReportEvents::REPORT_ON_DISPLAY, $event);
            $data = $event->getData();
        }

        if (MAUTIC_ENV == 'dev') {
            $debugData['query'] = $query->getSQL();
            $params             = $query->getParameters();

            foreach ($params as $name => $param) {
                $debugData['query'] = str_replace(":$name", "'$param'", $debugData['query']);
            }

            $debugData['query_time'] = (isset($queryTime)) ? $queryTime : 'N/A';
        }

        return [
            'totalResults'    => $totalResults,
            'data'            => $data,
            'dataColumns'     => $dataColumns,
            'graphs'          => $graphs,
            'contentTemplate' => $contentTemplate,
            'columns'         => $tableDetails['columns'],
            'limit'           => ($paginate) ? $limit : 0,
            'dateFrom'        => $dataOptions['dateFrom'],
            'dateTo'          => $dataOptions['dateTo'],
            'debug'           => $debugData,
        ];
    }

    /**
     * @return mixed
     */
    public function getReportsWithGraphs()
    {
        $ownedBy = $this->security->isGranted('report:reports:viewother') ? null : $this->userHelper->getUser()->getId();

        return $this->getRepository()->findReportsWithGraphs($ownedBy);
    }

    /**
     * Determine what operators should be used for the filter type.
     *
     * @param array $data
     *
     * @return mixed|string
     */
    private function getOperatorOptions(array $data)
    {
        if (isset($data['operators'])) {
            // Custom operators
            $options = $data['operators'];
        } else {
            $operator = (isset($data['operatorGroup'])) ? $data['operatorGroup'] : $data['type'];

            if (!array_key_exists($operator, MauticReportBuilder::OPERATORS)) {
                $operator = 'default';
            }

            $options = MauticReportBuilder::OPERATORS[$operator];
        }

        foreach ($options as $value => &$label) {
            $label = $this->translator->trans($label);
        }

        return $options;
    }
}
