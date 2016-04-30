<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Model;

use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Entity\Action;
use Mautic\PointBundle\Entity\LeadPointLog;
use Mautic\PointBundle\Entity\Point;
use Mautic\PointBundle\Event\PointBuilderEvent;
use Mautic\PointBundle\Event\PointEvent;
use Mautic\PointBundle\PointEvents;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\Chart\PieChart;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class PointModel
 */
class PointModel extends CommonFormModel
{

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\PointBundle\Entity\PointRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticPointBundle:Point');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'point:points';
    }

    /**
     * {@inheritdoc}
     *
     * @throws MethodNotAllowedHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = array())
    {
        if (!$entity instanceof Point) {
            throw new MethodNotAllowedHttpException(array('Point'));
        }
        if (!empty($action)) {
            $options['action'] = $action;
        }
        return $formFactory->create('point', $entity, $options);
    }

    /**
     * {@inheritdoc}
     *
     * @return Point|null
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new Point();
        }

        return parent::getEntity($id);
    }

    /**
     * {@inheritdoc}
     *
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof Point) {
            throw new MethodNotAllowedHttpException(array('Point'));
        }

        switch ($action) {
            case "pre_save":
                $name = PointEvents::POINT_PRE_SAVE;
                break;
            case "post_save":
                $name = PointEvents::POINT_POST_SAVE;
                break;
            case "pre_delete":
                $name = PointEvents::POINT_PRE_DELETE;
                break;
            case "post_delete":
                $name = PointEvents::POINT_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new PointEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);
            return $event;
        }

        return null;
    }

    /**
     * Gets array of custom actions from bundles subscribed PointEvents::POINT_ON_BUILD
     *
     * @return mixed
     */
    public function getPointActions()
    {
        static $actions;

        if (empty($actions)) {
            //build them
            $actions = array();
            $event = new PointBuilderEvent($this->translator);
            $this->dispatcher->dispatch(PointEvents::POINT_ON_BUILD, $event);
            $actions['actions'] = $event->getActions();
            $actions['list']    = $event->getActionList();
            $actions['choices'] = $event->getActionChoices();
        }

        return $actions;
    }

    /**
     * Triggers a specific point change
     *
     * @param $type
     * @param mixed $eventDetails passthrough from function triggering action to the callback function
     * @param mixed $typeId Something unique to the triggering event to prevent  unnecessary duplicate calls
     * @param Lead  $lead
     *
     * @return void
     */
    public function triggerAction($type, $eventDetails = null, $typeId = null, Lead $lead = null)
    {
        //only trigger actions for anonymous users
        if (!$this->security->isAnonymous()) {
            return;
        }

        if ($typeId !== null && $this->factory->getEnvironment() == 'prod') {
            //let's prevent some unnecessary DB calls
            $session = $this->factory->getSession();
            $triggeredEvents = $session->get('mautic.triggered.point.actions', array());
            if (in_array($typeId, $triggeredEvents)) {
                return;
            }
            $triggeredEvents[] = $typeId;
            $session->set('mautic.triggered.point.actions', $triggeredEvents);
        }

        //find all the actions for published points
        /** @var \Mautic\PointBundle\Entity\PointRepository $repo */
        $repo            = $this->getRepository();
        $availablePoints = $repo->getPublishedByType($type);
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel    = $this->factory->getModel('lead');
        $ipAddress    = $this->factory->getIpAddress();

        if (null === $lead) {
            $lead = $leadModel->getCurrentLead();

            if (null === $lead || !$lead->getId()) {

                return;
            }
        }

        //get available actions
        $availableActions = $this->getPointActions();

        //get a list of actions that has already been performed on this lead
        $completedActions = $repo->getCompletedLeadActions($type, $lead->getId());

        $persist = array();
        foreach ($availablePoints as $action) {
            //if it's already been done, then skip it
            if (isset($completedActions[$action->getId()])) {
                continue;
            }

            //make sure the action still exists
            if (!isset($availableActions['actions'][$action->getType()])) {
                continue;
            }
            $settings = $availableActions['actions'][$action->getType()];

            $args = array(
                'action'      => array(
                    'id'         => $action->getId(),
                    'type'       => $action->getType(),
                    'name'       => $action->getName(),
                    'properties' => $action->getProperties(),
                    'points'     => $action->getDelta()
                ),
                'lead'        => $lead,
                'factory'     => $this->factory,
                'eventDetails' => $eventDetails
            );

            $callback = (isset($settings['callback'])) ? $settings['callback'] :
                array('\\Mautic\\PointBundle\\Helper\\EventHelper', 'engagePointAction');

            if (is_callable($callback)) {
                if (is_array($callback)) {
                    $reflection = new \ReflectionMethod($callback[0], $callback[1]);
                } elseif (strpos($callback, '::') !== false) {
                    $parts      = explode('::', $callback);
                    $reflection = new \ReflectionMethod($parts[0], $parts[1]);
                } else {
                    $reflection = new \ReflectionMethod(null, $callback);
                }

                $pass = array();
                foreach ($reflection->getParameters() as $param) {
                    if (isset($args[$param->getName()])) {
                        $pass[] = $args[$param->getName()];
                    } else {
                        $pass[] = null;
                    }
                }
                $pointsChange = $reflection->invokeArgs($this, $pass);

                if ($pointsChange) {
                    $delta = $action->getDelta();
                    $lead->addToPoints($delta);
                    $parsed = explode('.', $action->getType());
                    $lead->addPointsChangeLogEntry(
                        $parsed[0],
                        $action->getId() . ": " . $action->getName(),
                        $parsed[1],
                        $delta,
                        $ipAddress
                    );

                    $log = new LeadPointLog();
                    $log->setIpAddress($ipAddress);
                    $log->setPoint($action);
                    $log->setLead($lead);
                    $log->setDateFired(new \DateTime());

                    $persist[] = $log;
                }
            }
        }

        if (!empty($persist)) {
            $leadModel->saveEntity($lead);
            $this->getRepository()->saveEntities($persist);

            // Detach logs to reserve memory
            $this->em->clear('Mautic\PointBundle\Entity\LeadPointLog');
        }
    }

    /**
     * Get line chart data of points
     *
     * @param char     $unit   {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param DateTime $dateFrom
     * @param DateTime $dateTo
     * @param string   $dateFormat
     * @param array    $filter
     * @param boolean  $canViewOthers
     *
     * @return array
     */
    public function getPointLineChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, $filter = array(), $canViewOthers = true)
    {
        $chart     = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query     = $chart->getChartQuery($this->factory->getEntityManager()->getConnection());
        $q         = $query->prepareTimeDataQuery('lead_points_change_log', 'date_added', $filter);

        if (!$canViewOthers) {
            $q->join('t', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = t.lead_id')
                ->andWhere('l.owner_id = :userId')
                ->setParameter('userId', $this->factory->getUser()->getId());
        }

        $data = $query->loadAndBuildTimeData($q);
        $chart->setDataset($this->factory->getTranslator()->trans('mautic.point.changes'), $data);
        return $chart->render();
    }
}
