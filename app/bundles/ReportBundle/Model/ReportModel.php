<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Model;

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportEvent;
use Mautic\ReportBundle\Generator\ReportGenerator;
use Mautic\ReportBundle\ReportEvents;
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
    protected function dispatchEvent ($action, &$entity, $isNew = false, $event = false)
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
                return false;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new ReportEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return false;
        }
    }

    /**
     * Build the table and graph data
     *
     * @param $context
     *
     * @return mixed
     */
    public function buildAvailableReports($context)
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

        return $data['tables'];
    }

    /**
     * @param string $context
     *
     * @return mixed
     */
    public function getGraphData ($context = 'all')
    {
        $data = $this->buildAvailableReports($context);

        return $data['graphs'];
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
        }

        return array($columnList, htmlspecialchars(json_encode($typeList), ENT_QUOTES, 'UTF-8'));
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
        foreach ($graphData as $key => $type) {
            $translated[$key] = $this->translator->trans($key) . " (" . $this->translator->trans('mautic.report.graph.' . $type);
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
        $name      = str_replace(' ', '_', $date) . '_' . InputHelper::alphanum($report->getName(), false, true);

        switch ($format) {
            case 'csv':
                $response = new StreamedResponse(function () use ($reportData, $report, $formatter) {
                    $handle = fopen('php://output', 'r+');
                    $header = array();

                    //build the data rows
                    foreach ($reportData['data'] as $count => $data) {
                        $row   = array();
                        foreach ($data as $k => $v) {
                            if ($count === 0) {
                                //set the header
                                $header[] = $k;
                            }
                            $row[] = $formatter->_($v, $reportData['columns'][$k]['type'], true);
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
                            $row   = array();
                            foreach ($data as $k => $v) {
                                if ($count === 0) {
                                    //set the header
                                    $header[] = $k;
                                }
                                $row[] = $formatter->_($v, $reportData['columns'][$k]['type'], true);
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
}
