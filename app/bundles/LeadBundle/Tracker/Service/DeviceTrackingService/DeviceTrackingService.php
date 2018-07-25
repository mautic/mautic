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
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
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
     * @var LeadDevice
     */
    private $trackedDevice;

    /**
     * @var CorePermissions
     */
    private $security;

    /**
     * DeviceTrackingService constructor.
     *
     * @param CookieHelper           $cookieHelper
     * @param EntityManagerInterface $entityManager
     * @param LeadDeviceRepository   $leadDeviceRepository
     * @param RandomHelperInterface  $randomHelper
     * @param RequestStack           $requestStack
     * @param CorePermissions        $security
     */
    public function __construct(
        CookieHelper $cookieHelper,
        EntityManagerInterface $entityManager,
        LeadDeviceRepository $leadDeviceRepository,
        RandomHelperInterface $randomHelper,
        RequestStack $requestStack,
        CorePermissions $security
    ) {
        $this->cookieHelper           = $cookieHelper;
        $this->entityManager          = $entityManager;
        $this->randomHelper           = $randomHelper;
        $this->leadDeviceRepository   = $leadDeviceRepository;
        $this->request                = $requestStack->getCurrentRequest();
        $this->security               = $security;
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
        if (!$this->security->isAnonymous()) {
            // Do not track Mautic users
            return;
        }

        if ($this->trackedDevice) {
            return $this->trackedDevice;
        }

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
        if (null !== $trackedDevice && false === $replaceExistingTracking) {
            return $trackedDevice;
        }

        // Check for an existing device for this contact to prevent blowing up the devices table
        $existingDevice = $this->leadDeviceRepository->findOneBy(
            [
                'lead'        => $device->getLead(),
                'device'      => $device->getDevice(),
                'deviceBrand' => $device->getDeviceBrand(),
                'deviceModel' => $device->getDeviceModel(),
            ]
        );

        if (null !== $existingDevice) {
            $device = $existingDevice;
        }

        if (null === $device->getTrackingId()) {
            // Ensure all devices have a tracking ID (new devices will not and pre 2.13.0 devices may not)
            $device->setTrackingId($this->getUniqueTrackingIdentifier());

            $this->entityManager->persist($device);
            $this->entityManager->flush();
        }

        $this->createTrackingCookies($device);

        // Store the device in case a service uses this within the same session
        $this->trackedDevice = $device;

        return $device;
    }

    public function clearTrackingCookies()
    {
        $this->cookieHelper->deleteCookie('mautic_device_id');
        $this->cookieHelper->deleteCookie('mtc_id');
        $this->cookieHelper->deleteCookie('mtc_sid');

        $this->clearBcTrackingCookies();
    }

    /**
     * @return string|null
     */
    private function getTrackedIdentifier()
    {
        if ($this->request === null) {
            return null;
        }

        if ($this->trackedDevice) {
            // Use the device tracked in case the cookies were just created
            return $this->trackedDevice->getTrackingId();
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

    /**
     * @param LeadDevice $device
     */
    private function createTrackingCookies(LeadDevice $device)
    {
        $this->clearBcTrackingCookies();

        // Device cookie
        $this->cookieHelper->setCookie('mautic_device_id', $device->getTrackingId(), 31536000);

        // Mainly for landing pages so that JS has the same access as 3rd party tracking code
        $this->cookieHelper->setCookie('mtc_id', $device->getLead()->getId(), null);
        $this->cookieHelper->setCookie('mtc_sid', $device->getTrackingId(), null);

        $this->createBcTrackingCookies($device);
    }

    /**
     * @deprecated 2.13.0 to be removed in 3.0
     *
     * @param LeadDevice $device
     */
    private function createBcTrackingCookies(LeadDevice $device)
    {
        $this->cookieHelper->setCookie('mautic_session_id', $device->getTrackingId(), 31536000);
        $this->cookieHelper->setCookie($device->getTrackingId(), $device->getLead()->getId(), 31536000);
    }

    private function clearBcTrackingCookies()
    {
        // Delete old cookies
        if ($deviceTrackingId = $this->getTrackedIdentifier()) {
            $this->cookieHelper->deleteCookie($deviceTrackingId);
        }

        $this->cookieHelper->deleteCookie('mautic_session_id');
    }
}
