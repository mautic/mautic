<?php

namespace Mautic\LeadBundle\Model\Service;

use DeviceDetector\DeviceDetector;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Entity\LeadDeviceRepository;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class DeviceTrackingService.
 */
final class DeviceTrackingService implements DeviceTrackingServiceInterface
{
    /** @var CookieHelper */
    private $cookieHelper;

    /** @var EntityManager */
    private $entityManager;

    /** @var LeadDeviceRepository */
    private $leadDeviceRepository;

    /** @var Request|null */
    private $request;

    /**
     * DeviceTrackingService constructor.
     *
     * @param CookieHelper         $cookieHelper
     * @param EntityManager        $entityManager
     * @param LeadDeviceRepository $leadDeviceRepository
     * @param RequestStack         $requestStack
     */
    public function __construct(
        CookieHelper $cookieHelper,
        EntityManager $entityManager,
        LeadDeviceRepository $leadDeviceRepository,
        RequestStack $requestStack
    ) {
        $this->cookieHelper         = $cookieHelper;
        $this->entityManager        = $entityManager;
        $this->leadDeviceRepository = $leadDeviceRepository;
        $this->request              = $requestStack->getCurrentRequest();
    }

    /**
     * @return bool
     */
    public function isTracked()
    {
        return $this->getTrackedDevice() !== null;
    }

    /**
     * @return LeadDevice
     */
    public function getTrackedDevice()
    {
        $trackingId = $this->getTrackedIdentifier();
        if ($trackingId === null) {
            return null;
        }

        return $this->leadDeviceRepository->getByTrackingId($trackingId);
    }

    /**
     * @param DeviceDetector $deviceDetector
     * @param bool           $replaceExisting
     * @param Lead|null      $assignedLead
     *
     * @return LeadDevice
     */
    public function trackCurrent(DeviceDetector $deviceDetector, $replaceExisting = false, Lead $assignedLead = null)
    {
        if ($assignedLead !== null) {
            @trigger_error('Parameter $assignedLead is deprecated and will be removed in 3.0', E_USER_DEPRECATED);
        }
        $device = $this->getTrackedDevice();
        if ($device !== null && $replaceExisting === false) {
            return $device;
        } elseif ($device === null) {
            $device = $this->getDeviceFromDetector($deviceDetector, $assignedLead);
        }
        $device->setTrackingId($this->getUniqueTrackingIdentifier());
        $this->cookieHelper->setCookie('mautic_device_id', $device->getTrackingId(), 31536000);

        return $device;
    }

    /**
     * @return string|null
     */
    private function getTrackedIdentifier()
    {
        if ($this->request === null) {
            return null;
        }
        $deviceTrackingId = $this->request->cookies->get('mautic_device_id', null);
        if ($deviceTrackingId === null) {
            if ($this->request->getMethod() === 'GET') {
                $deviceTrackingId = $this->request->query->get('mautic_device_id', null);
            } else {
                $deviceTrackingId = $this->request->request->get('mautic_device_id', null);
            }
        }

        return $deviceTrackingId;
    }

    /**
     * @param DeviceDetector $deviceDetector
     * @param Lead|null      $assignedLead
     *
     * @return LeadDevice
     */
    private function getDeviceFromDetector(DeviceDetector $deviceDetector, Lead $assignedLead = null)
    {
        $device = new LeadDevice();
        $device->setClientInfo($deviceDetector->getClient());
        $device->setDevice($deviceDetector->getDeviceName());
        $device->setDeviceBrand($deviceDetector->getBrand());
        $device->setDeviceModel($deviceDetector->getModel());
        $device->setDeviceOs($deviceDetector->getOs());
        $device->setDateAdded(new \DateTime());
        if ($assignedLead === null) {
            $assignedLead = new Lead();
        }
        $device->setLead($assignedLead);
        $this->entityManager->persist($device);

        return $device;
    }

    /**
     * @return string
     */
    private function getUniqueTrackingIdentifier()
    {
        do {
            $generatedIdentifier = hash('sha1', uniqid(mt_rand()));
            $device              = $this->leadDeviceRepository->getByTrackingId($generatedIdentifier);
        } while ($device !== null);

        return $generatedIdentifier;
    }
}
