<?php

namespace Mautic\LeadBundle\Model\Service;

use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ContactTrackingService.
 */
final class ContactTrackingService implements ContactTrackingServiceInterface
{
    /** @var CookieHelper */
    private $cookieHelper;

    /** @var LeadRepository */
    private $leadRepository;

    /** @var Request|null */
    private $request;

    /**
     * ContactTrackingService constructor.
     *
     * @param CookieHelper   $cookieHelper
     * @param LeadRepository $leadRepository
     * @param RequestStack   $requestStack
     */
    public function __construct(
        CookieHelper $cookieHelper,
        LeadRepository $leadRepository,
        RequestStack $requestStack
    ) {
        $this->cookieHelper   = $cookieHelper;
        $this->leadRepository = $leadRepository;
        $this->request        = $requestStack->getCurrentRequest();
    }

    /**
     * @return bool
     */
    public function isTracked()
    {
        return $this->getTrackedIdentifier() !== null;
    }

    /**
     * @return Lead|null
     */
    public function getTrackedLead()
    {
        $trackingId = $this->getTrackedIdentifier();
        if ($trackingId === null) {
            return null;
        }
        $leadId = $this->request->cookies->get($trackingId, null);
        if ($leadId === null) {
            $leadId = ('GET' == $this->request->getMethod())
                ?
                $this->request->query->get('mtc_id', null)
                :
                $this->request->request->get('mtc_id', null);
        }
        if ($leadId === null) {
            return null;
        }

        return $this->leadRepository->getEntity($leadId);
    }

    /**
     * @return string|null
     */
    public function getTrackedIdentifier()
    {
        if ($this->request === null) {
            return null;
        }

        return $this->request->cookies->get('mautic_session_id', null);
    }

    /**
     * @param Lead $lead
     * @param bool $replaceCurrent
     *
     * @return string
     */
    public function track(Lead $lead, $replaceCurrent = false)
    {
        $currentTrackingId = $this->getTrackedIdentifier();
        if ($currentTrackingId !== null) {
            if ($replaceCurrent === false) {
                return $currentTrackingId;
            }
            $this->cookieHelper->setCookie($currentTrackingId, null, -3600);
        }
        $trackingId = hash('sha1', uniqid(mt_rand()));
        $this->cookieHelper->setCookie('mautic_session_id', $trackingId, 31536000);
        $this->cookieHelper->setCookie($trackingId, $lead->getId(), 31536000);

        return $trackingId;
    }
}
