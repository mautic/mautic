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
use Mautic\PointBundle\Entity\Action;
use Mautic\PointBundle\Entity\LeadPointLog;
use Mautic\PointBundle\Entity\Point;
use Mautic\PointBundle\Event\PointBuilderEvent;
use Mautic\PointBundle\Event\PointEvent;
use Mautic\PointBundle\PointEvents;
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
    protected function dispatchEvent($action, &$entity, $isNew = false, $event = false)
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
                return false;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new PointEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);
            return $event;
        }

        return false;
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
     *
     * @return void
     */
    public function triggerAction($type, $eventDetails = null, $typeId = null)
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
        $repo         = $this->getRepository();
        $availablePoints = $repo->getPublishedByType($type);
        $leadModel    = $this->factory->getModel('lead');
        $lead         = $leadModel->getCurrentLead();
        $ipAddress    = $this->factory->getIpAddress();

        //get available actions
        $availableActions = $this->getPointActions();

        //get a list of actions that has already been performed on this lead
        $completedActions = $repo->getCompletedLeadActions($type, $lead->getId());

        $persist     = array();
        $persistLead = false;

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

                    $action->addLog($log);
                    $persist[] = $action;
                    $persistLead = true;
                }
            }
        }

        //save the lead
        if ($persistLead) {
            $leadModel->saveEntity($lead);
        }

        //persist the action xref
        if (!empty($persist)) {
            $this->getRepository()->saveEntities($persist);
        }
    }
}
