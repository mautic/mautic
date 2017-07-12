<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

use DeviceDetector\DeviceDetector;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Entity\LeadDeviceRepository;
use Mautic\LeadBundle\Event\LeadDeviceEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class DeviceModel
 * {@inheritdoc}
 */
class DeviceModel extends FormModel
{
    /**
     * {@inheritdoc}
     *
     * @return LeadDeviceRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:LeadDevice');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'lead:leads';
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     *
     * @param $id
     *
     * @return null|object
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new LeadDevice();
        }

        return parent::getEntity($id);
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
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof LeadDevice) {
            throw new MethodNotAllowedHttpException(['LeadDevice']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create('leaddevice', $entity, $options);
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
        if (!$entity instanceof LeadDevice) {
            throw new MethodNotAllowedHttpException(['LeadDevice']);
        }

        switch ($action) {
            case 'pre_save':
                $name = LeadEvents::DEVICE_PRE_SAVE;
                break;
            case 'post_save':
                $name = LeadEvents::DEVICE_POST_SAVE;
                break;
            case 'pre_delete':
                $name = LeadEvents::DEVICE_PRE_DELETE;
                break;
            case 'post_delete':
                $name = LeadEvents::DEVICE_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new LeadDeviceEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * @param Lead           $lead
     * @param                $userAgent
     * @param \DateTime|null $dateAdded
     *
     * @return LeadDevice|null
     */
    public function getContactDeviceFromUserAgent(Lead $lead, $userAgent, \DateTime $dateAdded = null, $fingerPrint = null)
    {
        $dd = new DeviceDetector($userAgent);
        $dd->parse();

        $device = $this->getRepository()->getDeviceEntity($lead, $dd->getDeviceName(), $dd->getBrand(), $dd->getModel());

        if (empty($device)) {
            if (null === $dateAdded) {
                $dateAdded = new \DateTime();
            }

            $device = new LeadDevice();
            $device->setClientInfo($dd->getClient());
            $device->setDevice($dd->getDeviceName());
            $device->setDeviceBrand($dd->getBrand());
            $device->setDeviceModel($dd->getModel());
            $device->setDeviceOs($dd->getOs());
            $device->setDateAdded($dateAdded);
            $device->setLead($lead);
            $device->setDeviceFingerprint($fingerPrint);

            $this->saveEntity($device);
        } elseif ($fingerPrint && $device->getDeviceFingerprint() !== $fingerPrint) {
            $device->setDeviceFingerprint($fingerPrint);
            $this->saveEntity($device);
        }

        return $device;
    }
}
