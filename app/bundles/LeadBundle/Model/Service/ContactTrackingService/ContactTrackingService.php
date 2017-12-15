<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model\Service\ContactTrackingService;

use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDeviceRepository;
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

    /** @var LeadDeviceRepository */
    private $leadDeviceRepository;

    /** @var LeadRepository */
    private $leadRepository;

    /** @var Request|null */
    private $request;

    /**
     * ContactTrackingService constructor.
     *
     * @param CookieHelper         $cookieHelper
     * @param LeadDeviceRepository $leadDeviceRepository
     * @param LeadRepository       $leadRepository
     * @param RequestStack         $requestStack
     */
    public function __construct(
        CookieHelper $cookieHelper,
        LeadDeviceRepository $leadDeviceRepository,
        LeadRepository $leadRepository,
        RequestStack $requestStack
    ) {
        $this->cookieHelper         = $cookieHelper;
        $this->leadDeviceRepository = $leadDeviceRepository;
        $this->leadRepository       = $leadRepository;
        $this->request              = $requestStack->getCurrentRequest();
    }

    /**
     * @return Lead|null
     */
    public function getTrackedLead()
    {
        if ($this->request === null) {
            return null;
        }
        $trackingId = $this->getTrackedIdentifier();
        if ($trackingId === null) {
            return null;
        }
        $leadId = $this->cookieHelper->getCookie($trackingId, null);
        if ($leadId === null) {
            $leadId = $this->request->get('mtc_id', null);
            if ($leadId === null) {
                return null;
            }
        }

        $lead                        = $this->leadRepository->getEntity($leadId);
        if ($lead === null) {
            return null;
        }
        $anotherDeviceAlreadyTracked = $this->leadDeviceRepository->isAnyLeadDeviceTracked($lead);

        return $anotherDeviceAlreadyTracked ? null : $lead;
    }

    /**
     * @return string|null
     */
    public function getTrackedIdentifier()
    {
        return $this->cookieHelper->getCookie('mautic_session_id', null);
    }
}
