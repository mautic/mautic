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
        $params = (!empty($action)) ? array('action' => $action) : array();
        return $formFactory->create('point', $entity, $params);
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
            throw new MethodNotAllowedHttpException(array('Form'));
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
     * @param Point $entity
     * @param       $sessionActions
     */
    public function setActions(Point &$entity, $sessionActions)
    {
        $order   = 1;
        $existingActions = $entity->getActions();

        foreach ($sessionActions as $properties) {
            $isNew = (!empty($properties['id']) && isset($existingActions[$properties['id']])) ? false : true;
            $action = !$isNew ? $existingActions[$properties['id']] : new Action();

            foreach ($properties as $f => $v) {
                if (in_array($f, array('id', 'order')))
                    continue;

                $func = "set" .  ucfirst($f);
                if (method_exists($action, $func)) {
                    $action->$func($v);
                }
                $action->setPoint($entity);
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
        $session          = $this->factory->getSession();
        $customComponents = $session->get('mautic.pointcomponents.custom');
        if (empty($customComponents)) {
            //build them
            $customComponents = array();
            $event            = new PointBuilderEvent($this->translator);
            $this->dispatcher->dispatch(PointEvents::POINT_ON_BUILD, $event);
            $customComponents['actions'] = $event->getActions();
            $session->set('mautic.pointcomponents.custom', $customComponents);
        }
        return $customComponents;
    }

    /**
     * Triggers a specific point change
     *
     * @param $type
     */
    public function triggerAction($type)
    {
        //find all the actions for published points
        $pointActions = $this->em->getRepository('MauticPointBundle:Action')->getPublishedByType($type);
        $leadModel    = $this->factory->getModel('lead');
        $lead         = $leadModel->getCurrentLead();
        $ipAddress    = $this->factory->getIpAddress();

        foreach ($pointActions as $action) {
            $args = array(
                'action'   => $action,
                'lead'     => $lead,
                'factory'  => $this->factory,
            );

            $callback = (isset($action['settings']['callback'])) ? $action['settings']['callback'] :
                array('\\Mautic\\PointBundle\\Helper\\EventHelper', 'engagePointAction');

            if (is_callable($callback)) {
                if (is_array($callback)) {
                    $reflection = new \ReflectionMethod($callback[0], $callback[1]);
                } elseif (strpos($callback, '::') !== false) {
                    $parts = explode('::', $callback);
                    $reflection = new \ReflectionMethod($parts[0], $parts[1]);
                } else {
                    new \ReflectionMethod(null, $callback);
                }

                $pass = array();
                foreach($reflection->getParameters() as $param) {
                    if(isset($args[$param->getName()])) {
                        $pass[] = $args[$param->getName()];
                    } else {
                        $pass[] = null;
                    }
                }
                $scoreChange = $reflection->invokeArgs($this, $pass);
                if ($scoreChange) {
                    $lead->addToScore($scoreChange);
                    $parsed = explode('.', $action['type']);
                    $lead->addScoreChangeLogEntry(
                        $parsed[0],
                        $action['point']['name'],
                        $action['name'],
                        $scoreChange,
                        $ipAddress
                    );
                }
            }
        }

        //save the lead
        $leadModel->saveEntity($lead);
    }
}