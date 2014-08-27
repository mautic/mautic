<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Model;

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Event\ReportEvent;
use Mautic\ReportBundle\Generator\ReportGenerator;
use Mautic\ReportBundle\ReportEvents;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class ReportModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
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
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'report:reports';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getNameGetter()
    {
        return "getTitle";
    }

    /**
     * {@inheritdoc}
     *
     * @param \Mautic\ReportBundle\Entity\Report           $entity
     * @param \Symfony\Component\Form\FormFactoryInterface $formFactory
     * @param null                                         $action
     * @param array                                        $options
     * @return \Symfony\Component\Form\Form
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = array())
    {
        if (!$entity instanceof Report) {
            throw new MethodNotAllowedHttpException(array('Report'));
        }

        $params = (!empty($action)) ? array('action' => $action) : array();
        $params['read_only'] = false;

        $reportGenerator = new ReportGenerator($this->em, $this->factory->getSecurityContext(), $formFactory);

        return $reportGenerator->getForm($entity, $params);
    }

    /**
     * Get a specific entity or generate a new one if id is empty
     *
     * @param $id
     * @return Report
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
     * @param $action
     * @param $event
     * @param $entity
     * @param $isNew
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, $event = false)
    {
        if (!$entity instanceof Page) {
            throw new MethodNotAllowedHttpException(array('Page'));
        }

        switch ($action) {
            case "pre_save":
                $name = ReportEvents::PAGE_PRE_SAVE;
                break;
            case "post_save":
                $name = ReportEvents::PAGE_POST_SAVE;
                break;
            case "pre_delete":
                $name = ReportEvents::PAGE_PRE_DELETE;
                break;
            case "post_delete":
                $name = ReportEvents::PAGE_POST_DELETE;
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
     * Get list of entities for autopopulate fields
     *
     * @param $type
     * @param $filter
     * @param $limit
     * @return array
     */
    public function getLookupResults($type, $filter = '', $limit = 10)
    {
        $results = array();
        switch ($type) {
            case 'report':
                $viewOther = $this->security->isGranted('report:reports:viewother');
                $repo      = $this->getRepository();
                $repo->setCurrentUser($this->factory->getUser());
                $results = $repo->getPageList($filter, $limit, 0, $viewOther);
                break;
        }

        return $results;
    }
}
