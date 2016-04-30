<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Model;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportEvent;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Mautic\ReportBundle\Generator\ReportGenerator;
use Mautic\ReportBundle\ReportEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class ReportModel
 */
class ReportModel extends FormModel
{

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\ReportBundle\Entity\ReportRepository
     */
    public function getRepository ()
    {
        return $this->em->getRepository('MauticReportBundle:Report');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase ()
    {
        return 'report:reports';
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm ($entity, $formFactory, $action = null, $options = array())
    {
        if (!$entity instanceof Report) {
            throw new MethodNotAllowedHttpException(array('Report'));
        }

        $params              = (!empty($action)) ? array('action' => $action) : array();
        $params['read_only'] = false;

        // Fire the REPORT_ON_BUILD event off to get the table/column data

        $params['table_list'] = $this->getTableData();

        $reportGenerator = new ReportGenerator($this->factory->getSecurityContext(), $formFactory, $entity);

        return $reportGenerator->getForm($entity, $params);
    }

    /**
     * {@inheritdoc}
     *
     * @return Report|null
     */
    public function getEntity ($id = null)
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
    protected function dispatchEvent ($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof Report) {
            throw new MethodNotAllowedHttpException(array('Report'));
        }

        switch ($action) {
            case "pre_save":
                $name = ReportEvents::REPORT_PRE_SAVE;
                break;
            case "post_save":
                $name = ReportEvents::REPORT_POST_SAVE;
                break;
            case "pre_delete":
                $name = ReportEvents::REPORT_PRE_DELETE;
                break;
            case "post_delete":
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
     * Build the table and graph data
     *
     * @param $context
     *
     * @return mixed
     */
    public function buildAvailableReports ($context)
    {
        static $data = array();

        if (empty($data[$context])) {
            // Check to see if all has been obtained
            if (isset($data['all'])) {
                $data[$context]['tables'] =& $data['all']['tables'][$context];
                $data[$context]['graphs'] =& $data['all']['graphs'][$context];
            } else {
                //build them
                $eventContext = ($context == 'all') ? '' : $context;
                $event        = new ReportBuilderEvent($this->factory->getTranslator(), $eventContext);
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
     * Builds the table lookup data for the report forms
     *
     * @param string $context
     *
     * @return array
     */
    public function getTableData ($context = 'all')
    {
        $data = $this->buildAvailableReports($context);
        return (!isset($data['tables'])) ? array() : $data['tables'];
    }

    /**
     * @param string $context
     *
     * @return mixed
     */
    public function getGraphData ($context = 'all')
    {
        $data = $this->buildAvailableReports($context);

        return (!isset($data['graphs'])) ? array() : $data['graphs'];
    }

    /**
     * @param string $context
     * @param bool   $asOptionHtml
     *
     * @return array
     */
    public function getColumnList ($context, $asOptionHtml = false)
    {
        $tableData = $this->getTableData($context);
        $columns   = $tableData['columns'];

        if ($asOptionHtml) {
            $columnList = '';
            $typeList   = array();
            foreach ($columns as $column => $data) {
                if (isset($data['label'])) {
                    $columnList .= '<option value="' . $column . '">' . $data['label'] . "</option>\n";
                    $typeList[$column] = $data['type'];
                }
            }
        } else {
            $columnList = $typeList = array();
            foreach ($columns as $column => $data) {
                if (isset($data['label'])) {
                    $columnList[$column] = $data['label'];
                    $typeList[$column]   = $data['type'];
                }
            }

            $typeList = htmlspecialchars(json_encode($typeList), ENT_QUOTES, 'UTF-8');
        }

        return array($columnList, $typeList);
    }

    /**
     * @param string $context
     * @param bool   $asOptionHtml
     *
     * @return array
     */
    public function getGraphList ($context, $asOptionHtml = false)
    {
        $graphData = $this->getGraphData($context);

        // First sort
        $translated = array();
        foreach ($graphData as $key => $details) {
            $translated[$key] = $this->translator->trans($key) . " (" . $this->translator->trans('mautic.report.graph.' . $details['type']);
        }
        asort($translated);

        if ($asOptionHtml) {
            $graphList = '';
            foreach ($translated as $key => $value) {
                $graphList .= '<option value="' . $key . '">' . $value . ")</option>\n";
            }

            return $graphList;
        }

        return $translated;
    }

    /**
     * Export report
     *
     * @param $format
     * @param $report
     * @param $reportData
     *
     * @return StreamedResponse|Response
     * @throws \Exception
     */
    public function exportResults ($format, $report, $reportData)
    {
        $formatter = $this->factory->getHelper('template.formatter');
        $date      = $this->factory->getDate()->toLocalString();
        $name      = str_replace(' ', '_', $date) . '_' . InputHelper::alphanum($report->getName(), false, '-');

        switch ($format) {
            case 'csv':

                $response = new StreamedResponse(function () use ($reportData, $report, $formatter) {
                    $handle = fopen('php://output', 'r+');
                    $header = array();

                    //build the data rows
                    foreach ($reportData['data'] as $count => $data) {
                        $row = array();
                        foreach ($data as $k => $v) {
                            if ($count === 0) {
                                //set the header
                                $header[] = $k;
                            }

                            $row[] = $formatter->_($v, $reportData['columns'][$reportData['dataColumns'][$k]]['type'], true);
                        }

                        if ($count === 0) {
                            //write the row
                            fputcsv($handle, $header);
                        } else {
                            fputcsv($handle, $row);
                        }

                        //free memory
                        unset($row, $reportData['data'][$k]);
                    }

                    fclose($handle);
                });

                $response->headers->set('Content-Type', 'application/force-download');
                $response->headers->set('Content-Type', 'application/octet-stream');
                $response->headers->set('Content-Disposition', 'attachment; filename="' . $name . '.csv"');
                $response->headers->set('Expires', 0);
                $response->headers->set('Cache-Control', 'must-revalidate');
                $response->headers->set('Pragma', 'public');

                return $response;
            case 'html':
                $content = $this->factory->getTemplating()->renderResponse(
                    'MauticReportBundle:Report:export.html.php',
                    array(
                        'data'      => $reportData['data'],
                        'columns'   => $reportData['columns'],
                        'pageTitle' => $name,
                        'graphs'    => $reportData['graphs'],
                        'report'    => $report
                    )
                )->getContent();

                return new Response($content);
            case 'xlsx':
                if (class_exists('PHPExcel')) {
                    $response = new StreamedResponse(function () use ($formatter, $reportData, $report, $name) {
                        $objPHPExcel = new \PHPExcel();
                        $objPHPExcel->getProperties()->setTitle($name);

                        $objPHPExcel->createSheet();
                        $header = array();

                        //build the data rows
                        foreach ($reportData['data'] as $count => $data) {
                            $row = array();
                            foreach ($data as $k => $v) {
                                if ($count === 0) {
                                    //set the header
                                    $header[] = $k;
                                }
                                $row[] = $formatter->_($v, $reportData['columns'][$reportData['dataColumns'][$k]]['type'], true);
                            }

                            //write the row
                            if ($count === 0) {
                                $objPHPExcel->getActiveSheet()->fromArray($header, NULL, 'A1');
                            } else {
                                $rowCount = $count + 1;
                                $objPHPExcel->getActiveSheet()->fromArray($row, NULL, "A{$rowCount}");
                            }

                            //free memory
                            unset($row, $reportData['data'][$k]);
                        }


                        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
                        $objWriter->setPreCalculateFormulas(false);

                        $objWriter->save('php://output');
                    });
                    $response->headers->set('Content-Type', 'application/force-download');
                    $response->headers->set('Content-Type', 'application/octet-stream');
                    $response->headers->set('Content-Disposition', 'attachment; filename="' . $name . '.xlsx"');
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
     * Get report data for view rendering
     *
     * @param       $entity
     * @param       $formFactory
     * @param array $options
     *
     * @return array
     */
    public function getReportData ($entity, $formFactory, $options = array())
    {
        $paginate   = !empty($options['paginate']);
        $reportPage = (isset($options['reportPage'])) ? $options['reportPage'] : 1;
        $data       = $graphs = array();;
        $reportGenerator = new ReportGenerator($this->factory->getSecurityContext(), $formFactory, $entity);

        $selectedColumns = $entity->getColumns();
        $totalResults    = $limit = 0;

        // Prepare the query builder
        $columns = $this->getTableData($entity->getSource());

        $orderBy    = $this->factory->getSession()->get('mautic.report.' . $entity->getId() . '.orderby', '');
        $orderByDir = $this->factory->getSession()->get('mautic.report.' . $entity->getId() . '.orderbydir', 'ASC');

        $dataOptions = array(
            //'start'      => $start,
            //'limit'      => $limit,
            'order'       => (!empty($orderBy)) ? array($orderBy, $orderByDir) : false,
            'dispatcher'  => $this->factory->getDispatcher(),
            'columns'     => $columns['columns']
        );

        /** @var \Doctrine\DBAL\Query\QueryBuilder $query */
        $query   = $reportGenerator->getQuery($dataOptions);
        $filters = $this->factory->getSession()->get('mautic.report.' . $entity->getId() . '.filters', array());
        if (!empty($filters)) {
            $filterParameters  = array();
            $filterExpressions = $query->expr()->andX();
            $repo              = $this->getRepository();
            foreach ($filters as $f) {
                list ($expr, $parameters) = $repo->getFilterExpr($query, $f);
                $filterExpressions->add($expr);
                if (is_array($parameters)) {
                    $filterParameters = array_merge($filterParameters, $parameters);
                }
            }
            $query->andWhere($filterExpressions);
            $query->setParameters($filterParameters);
        }
        $contentTemplate = $reportGenerator->getContentTemplate();

        //set what page currently on so that we can return here after form submission/cancellation
        $this->factory->getSession()->set('mautic.report.' . $entity->getId() . '.page', $reportPage);

        // Reset the orderBy as it causes errors in graphs and the count query in table data
        $parts  = $query->getQueryParts();
        $order  = $parts['orderBy'];
        $query->resetQueryPart('orderBy');

        if (empty($options['ignoreGraphData'])) {
            // Check to see if this is an update from AJAX
            $selectedGraphs = (!empty($options['graphName'])) ? array($options['graphName']) : $entity->getGraphs();
            if (!empty($selectedGraphs)) {
                $availableGraphs = $this->getGraphData($entity->getSource());
                if (empty($query)) {
                    $query = $reportGenerator->getQuery();
                }

                $eventGraphs = array();
                foreach ($selectedGraphs as $g) {
                    if (isset($availableGraphs[$g])) {
                        $graphOptions = isset($availableGraphs[$g]['options']) ? $availableGraphs[$g]['options'] : array();

                        if (!empty($options['graphName'])) {
                            $graphOptions = array_merge($graphOptions, $options);
                        }
                        $eventGraphs[$g] = array(
                            'options' => $graphOptions,
                            'type'    => $availableGraphs[$g]['type']
                        );
                    }
                }

                $event = new ReportGraphEvent($entity, $eventGraphs, $query);
                $this->factory->getDispatcher()->dispatch(ReportEvents::REPORT_ON_GRAPH_GENERATE, $event);
                $graphs = $event->getGraphs();
            }
        }

        if (empty($options['ignoreTableData']) && !empty($selectedColumns)) {
            if ($paginate) {
                // Build the options array to pass into the query
                $limit = $this->factory->getSession()->get('mautic.report.' . $entity->getId() . '.limit', $this->factory->getParameter('default_pagelimit'));
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

            $data = $query->execute()->fetchAll();

            if (!$paginate) {
                $totalResults = count($data);
            }
        }

        // Build a reference for column to data
        $dataColumns = array();
        foreach ($columns['columns'] as $dbColumn => $columnData) {
            $dataColumns[$columnData['label']] = $dbColumn;
        }

        return array(
            'totalResults'    => $totalResults,
            'data'            => $data,
            'graphs'          => $graphs,
            'contentTemplate' => $contentTemplate,
            'columns'         => $columns['columns'],
            'dataColumns'     => $dataColumns,
            'limit'           => ($paginate) ? $limit : 0
        );
    }
}