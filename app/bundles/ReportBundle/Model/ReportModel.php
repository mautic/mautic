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
    public function createForm($entity, $formFactory, $action = null, $options = array())
    {
        if (!$entity instanceof Report) {
            throw new MethodNotAllowedHttpException(array('Report'));
        }

        $params = (!empty($action)) ? array('action' => $action) : array();
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
    protected function dispatchEvent($action, &$entity, $isNew = false, $event = false)
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
     * Builds the table lookup data for the report forms
     *
     * @return array
     */
    public function getTableData()
    {
        static $tableData;

        if (empty($tableData)) {
            //build them
            $event = new ReportBuilderEvent($this->factory->getTranslator());
            $this->dispatcher->dispatch(ReportEvents::REPORT_ON_BUILD, $event);
            $tableData = $event->getTables();
        }

        return $tableData;
    }

    /**
     * @param string $source
     *
     * @return array
     */
    public function getColumnList($source = '', $asOptionHtml = false)
    {
        $tableData = $this->getTableData();
        if (!$source) {
            $source = key($tableData);
        }
        $columns = $tableData[$source]['columns'];

        if ($asOptionHtml) {
            $columnList = '';
            $typeList   = array();
            foreach ($columns as $column => $data) {
                if (isset($data['label'])) {
                    $columnList       .= '<option value="'.$column.'">'.$data['label']."</option>\n";
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
}
