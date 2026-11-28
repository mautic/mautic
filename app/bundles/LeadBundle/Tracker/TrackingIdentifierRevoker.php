<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tracker;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDeviceRepository;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Takes back the identifiers that let Mautic recognise a contact again once they turned tracking off.
 */
final readonly class TrackingIdentifierRevoker
{
    private const COOKIES = ['mautic_device_id', 'mtc_id', 'mautic_referer_id', 'mautic_session_id'];

    public function __construct(
        private CookieHelper $cookieHelper,
        private LeadDeviceRepository $leadDeviceRepository,
        private EntityManagerInterface $entityManager,
        private DeviceTracker $deviceTracker,
    ) {
    }

    public function revokeContact(Lead $contact): void
    {
        // Without its tracking ID, a device ID kept in a cookie or in localStorage no longer leads back to the contact
        foreach ($this->leadDeviceRepository->findBy(['lead' => $contact]) as $device) {
            if (null !== $device->getTrackingId()) {
                $device->setTrackingId(null);
                $this->entityManager->persist($device);
            }
        }

        $this->entityManager->flush();
        $this->deviceTracker->reset();
        $this->expireCookies();
    }

    private function expireCookies(): void
    {
        $names = self::COOKIES;

        // Contacts tracked before 2.13.0 also have a cookie named after their session ID that holds the contact ID
        $sessionId = $this->cookieHelper->getCookie('mautic_session_id');
        if (is_string($sessionId) && '' !== $sessionId) {
            $names[] = $sessionId;
        }

        foreach ($names as $name) {
            // SameSite=None like when they were written, so the browser also takes the expiry from a request sent by another site
            $this->cookieHelper->deleteCookie($name, sameSite: Cookie::SAMESITE_NONE);
        }
    }
}
