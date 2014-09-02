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
use Mautic\PointBundle\Entity\Range;
use Mautic\PointBundle\Entity\RangeAction;
use Mautic\PointBundle\Event\RangeBuilderEvent;
use Mautic\PointBundle\PointEvents;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class RangeModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class RangeModel extends CommonFormModel
{

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticPointBundle:Range');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'point:ranges';
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
        if (!$entity instanceof Range) {
            throw new MethodNotAllowedHttpException(array('Range'));
        }
        $params = (!empty($action)) ? array('action' => $action) : array();
        return $formFactory->create('pointrange', $entity, $params);
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
            return new Range();
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
        if (!$entity instanceof Range) {
            throw new MethodNotAllowedHttpException(array('Range'));
        }

        switch ($action) {
            case "pre_save":
                $name = PointEvents::RANGE_PRE_SAVE;
                break;
            case "post_save":
                $name = PointEvents::RANGE_POST_SAVE;
                break;
            case "pre_delete":
                $name = PointEvents::RANGE_PRE_DELETE;
                break;
            case "post_delete":
                $name = PointEvents::RANGE_POST_DELETE;
                break;
            default:
                return false;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new RangeEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);
            return $event;
        } else {
            return false;
        }
    }

    /**
     * @param Range $entity
     * @param       $sessionActions
     */
    public function setActions(Range &$entity, $sessionActions)
    {
        $order   = 1;
        $existingActions = $entity->getActions();

        foreach ($sessionActions as $properties) {
            $isNew = (!empty($properties['id']) && isset($existingActions[$properties['id']])) ? false : true;
            $action = !$isNew ? $existingActions[$properties['id']] : new RangeAction();

            foreach ($properties as $f => $v) {
                if (in_array($f, array('id', 'order')))
                    continue;

                $func = "set" .  ucfirst($f);
                if (method_exists($action, $func)) {
                    $action->$func($v);
                }
                $action->setRange($entity);
            }
            $action->setOrder($order);
            $order++;
            $entity->addAction($properties['id'], $action);
        }
    }

    /**
     * Gets array of custom actions from bundles subscribed PointEvents::POINT_ON_BUILD
     * @return mixed
     */
    public function getCustomComponents()
    {
        static $components;

        if (empty($components)) {
            //build them
            $customComponents = array();
            $event            = new RangeBuilderEvent($this->translator);
            $this->dispatcher->dispatch(PointEvents::RANGE_ON_BUILD, $event);
            $customComponents['actions'] = $event->getActions();
        }
        return $customComponents;
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
        $repo         = $this->em->getRepository('MauticPointBundle:RangeAction');
        $rangeActions = $repo->getPublishedByType($type);
        $leadModel    = $this->factory->getModel('lead');
        $lead         = $leadModel->getCurrentLead();

        //get a list of actions that has already been performed on this lead
        $completedActions = $repo->getCompletedLeadActions($type, $lead->getId());
        //if it's already been done, then skip it
        if (in_array($type, $completedActions)) {
            return;
        }

        foreach ($rangeActions as $action) {
            $range    = $action->getRange();
            $settings = $action->getSettings();
            $args     = array(
                'action'      => array(
                    'id'         => $action->getId(),
                    'type'       => $action->getType(),
                    'name'       => $action->getName(),
                    'properties' => $action->getProperties(),
                    'settings'   => $settings,
                    'range'      => array(
                        'id'         => $range->getId(),
                        'name'       => $range->getName(),
                        'fromScore' => $range->getFromScore(),
                        'toScore'   => $range->getToScore(),
                        'color'      => $range->getColor()
                    )
                ),
                'lead'        => $lead,
                'factory'     => $this->factory,
                'passthrough' => $passthrough
            );

            if (is_callable($settings['callback'])) {
                if (is_array($settings['callback'])) {
                    $reflection = new \ReflectionMethod($settings['callback'][0], $settings['callback'][1]);
                } elseif (strpos($settings['callback'], '::') !== false) {
                    $parts      = explode('::', $settings['callback']);
                    $reflection = new \ReflectionMethod($parts[0], $parts[1]);
                } else {
                    new \ReflectionMethod(null, $settings['callback']);
                }

                $pass = array();
                foreach ($reflection->getParameters() as $param) {
                    if (isset($args[$param->getName()])) {
                        $pass[] = $args[$param->getName()];
                    } else {
                        $pass[] = null;
                    }
                }
                $reflection->invokeArgs($this, $pass);
            }
        }
    }
}