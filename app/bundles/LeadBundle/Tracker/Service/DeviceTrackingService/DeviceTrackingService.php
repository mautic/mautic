<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tracker\Service\DeviceTrackingService;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\RandomHelper\RandomHelperInterface;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Entity\LeadDeviceRepository;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class DeviceTrackingService.
 */
final class DeviceTrackingService implements DeviceTrackingServiceInterface
{
    /**
     * @var CookieHelper
     */
    private $cookieHelper;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LeadDeviceRepository
     */
    private $leadDeviceRepository;

    /**
     * @var RandomHelperInterface
     */
    private $randomHelper;

    /**
     * @var Request|null
     */
    private $request;

    /**
     * DeviceTrackingService constructor.
     *
     * @param CookieHelper           $cookieHelper
     * @param EntityManagerInterface $entityManager
     * @param LeadDeviceRepository   $leadDeviceRepository
     * @param RandomHelperInterface  $randomHelper
     * @param RequestStack           $requestStack
     */
    public function __construct(
        CookieHelper $cookieHelper,
        EntityManagerInterface $entityManager,
        LeadDeviceRepository $leadDeviceRepository,
        RandomHelperInterface $randomHelper,
        RequestStack $requestStack
    ) {
        $this->cookieHelper           = $cookieHelper;
        $this->entityManager          = $entityManager;
        $this->randomHelper           = $randomHelper;
        $this->leadDeviceRepository   = $leadDeviceRepository;
        $this->request                = $requestStack->getCurrentRequest();
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
     * @param LeadDevice $device
     * @param bool       $replaceExistingTracking
     *
     * @return LeadDevice
     */
    public function trackCurrentDevice(LeadDevice $device, $replaceExistingTracking = false)
    {
        $trackedDevice = $this->getTrackedDevice();
        if ($trackedDevice !== null && $replaceExistingTracking === false) {
            return $trackedDevice;
        }

        if ($device->getTrackingId() === null) {
            $device->setTrackingId($this->getUniqueTrackingIdentifier());
        }

        $this->entityManager->persist($device);
        $this->entityManager->flush();

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
        $deviceTrackingId = $this->cookieHelper->getCookie('mautic_device_id', null);
        if ($deviceTrackingId === null) {
            $deviceTrackingId = $this->request->get('mautic_device_id', null);
        }

        return $deviceTrackingId;
    }

    /**
     * @return string
     */
    private function getUniqueTrackingIdentifier()
    {
        do {
            $generatedIdentifier = $this->randomHelper->generate(23);
            $device              = $this->leadDeviceRepository->getByTrackingId($generatedIdentifier);
        } while ($device !== null);

        return $generatedIdentifier;
    }
}
