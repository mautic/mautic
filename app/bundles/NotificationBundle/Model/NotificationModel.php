<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\NotificationBundle\Entity\Notification;
use Mautic\NotificationBundle\Entity\Stat;
use Mautic\NotificationBundle\Event\NotificationEvent;
use Mautic\NotificationBundle\Event\NotificationClickEvent;
use Mautic\NotificationBundle\NotificationEvents;
use Mautic\PageBundle\Model\TrackableModel;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class NotificationModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class NotificationModel extends FormModel
{
    /**
     * @var TrackableModel
     */
    protected $pageTrackableModel;
    
    /**
     * NotificationModel constructor.
     * 
     * @param TrackableModel $pageTrackableModel
     */
    public function __construct(TrackableModel $pageTrackableModel)
    {
        $this->pageTrackableModel = $pageTrackableModel;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\NotificationBundle\Entity\NotificationRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticNotificationBundle:Notification');
    }

    /**
     * @return \Mautic\NotificationBundle\Entity\StatRepository
     */
    public function getStatRepository()
    {
        return $this->em->getRepository('MauticNotificationBundle:Stat');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'notification:notifications';
    }

    /**
     * Save an array of entities
     *
     * @param  $entities
     * @param  $unlock
     *
     * @return array
     */
    public function saveEntities($entities, $unlock = true)
    {
        //iterate over the results so the events are dispatched on each delete
        $batchSize = 20;
        foreach ($entities as $k => $entity) {
            $isNew = ($entity->getId()) ? false : true;

            //set some defaults
            $this->setTimestamps($entity, $isNew, $unlock);

            if ($dispatchEvent = $entity instanceof Notification) {
                $event = $this->dispatchEvent("pre_save", $entity, $isNew);
            }

            $this->getRepository()->saveEntity($entity, false);

            if ($dispatchEvent) {
                $this->dispatchEvent("post_save", $entity, $isNew, $event);
            }

            if ((($k + 1) % $batchSize) === 0) {
                $this->em->flush();
            }
        }
        $this->em->flush();
    }

    /**
     * {@inheritdoc}
     *
     * @param       $entity
     * @param       $formFactory
     * @param null  $action
     * @param array $options
     *
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws MethodNotAllowedHttpException
     */
    public function createForm ($entity, $formFactory, $action = null, $options = array())
    {
        if (!$entity instanceof Notification) {
            throw new MethodNotAllowedHttpException(array('Notification'));
        }
        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create('notification', $entity, $options);
    }

    /**
     * Get a specific entity or generate a new one if id is empty
     *
     * @param $id
     *
     * @return null|Notification
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            $entity = new Notification;
        } else {
            $entity = parent::getEntity($id);
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     *
     * @param $action
     * @param $event
     * @param $entity
     * @param $isNew
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof Notification) {
            throw new MethodNotAllowedHttpException(array('Notification'));
        }

        switch ($action) {
            case "pre_save":
                $name = NotificationEvents::NOTIFICATION_PRE_SAVE;
                break;
            case "post_save":
                $name = NotificationEvents::NOTIFICATION_POST_SAVE;
                break;
            case "pre_delete":
                $name = NotificationEvents::NOTIFICATION_PRE_DELETE;
                break;
            case "post_delete":
                $name = NotificationEvents::NOTIFICATION_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new NotificationEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * @param $idHash
     *
     * @return Stat
     */
    public function getNotificationStatus($idHash)
    {
        return $this->getStatRepository()->getNotificationStatus($idHash);
    }

    /**
     * Search for an notification stat by notification and lead IDs
     *
     * @param $notificationId
     * @param $leadId
     *
     * @return array
     */
    public function getNotificationStatByLeadId($notificationId, $leadId)
    {
        return $this->getStatRepository()->findBy(
            array(
                'notification' => (int) $notificationId,
                'lead'  => (int) $leadId
            ),
            array('dateSent' => 'DESC')
        );
    }

    /**
     * Get an array of tracked links
     *
     * @param $notificationId
     *
     * @return array
     */
    public function getNotificationClickStats($notificationId)
    {
        return $this->pageTrackableModel->getTrackableList('notification', $notificationId);
    }
}
