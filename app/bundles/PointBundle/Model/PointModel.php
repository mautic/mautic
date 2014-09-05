<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Model;

use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\PointBundle\Entity\Action;
use Mautic\PointBundle\Entity\Point;
use Mautic\PointBundle\Event\PointBuilderEvent;
use Mautic\PointBundle\Event\PointEvent;
use Mautic\PointBundle\PointEvents;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class PointModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class PointModel extends CommonFormModel
{

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticPointBundle:Point');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'point:points';
    }

    /**
     * {@inheritdoc}
     *
     * @param      $entity
     * @param      $formFactory
     * @param null $action
     * @param array $options
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
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
     * Get a specific entity or generate a new one if id is empty
     *
     * @param $id
     * @return null|object
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new Point();
        }

        $entity = parent::getEntity($id);

        return $entity;
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
        } else {
            return false;
        }
    }

    /**
     * Gets array of custom actions from bundles subscribed PointEvents::POINT_ON_BUILD
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
        }

        return $actions;
    }

    /**
     * Triggers a specific point change
     *
     * @param $type
     * @param mixed $passsthrough passthrough from function triggering action to the callback function
     */
    public function triggerAction($type, $passthrough = null)
    {
        //only trigger actions for anonymous users
        if (!$this->security->isAnonymous()) {
            return;
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
                ),
                'lead'        => $lead,
                'factory'     => $this->factory,
                'passthrough' => $passthrough
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
                    new \ReflectionMethod(null, $callback);
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
                    $lead->addToPoints($pointsChange);
                    $parsed = explode('.', $action->getType());
                    $lead->addPointsChangeLogEntry(
                        $parsed[0],
                        $action->getId() . ": " . $action->getName(),
                        $parsed[1],
                        $pointsChange,
                        $ipAddress
                    );
                    $action->addLead($lead);
                    $persist[] = $action;
                }
            }
        }

        //save the lead
        $leadModel->saveEntity($lead);

        //persist the action xref
        if (!empty($persist)) {
            $this->getRepository()->saveEntities($persist);
        }
    }
}