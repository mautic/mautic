<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tracker\Service\ContactTrackingService;

use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDeviceRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Entity\MergeRecordRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ContactTrackingService.
 *
 * Used to ensure that contacts tracked under the old method are continued to be tracked under the new
 */
final class ContactTrackingService implements ContactTrackingServiceInterface
{
    /**
     * @var CookieHelper
     */
    private $cookieHelper;

    /**
     * @var LeadDeviceRepository
     */
    private $leadDeviceRepository;

    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * @var MergeRecordRepository
     */
    private $mergeRecordRepository;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * ContactTrackingService constructor.
     */
    public function __construct(
        CookieHelper $cookieHelper,
        LeadDeviceRepository $leadDeviceRepository,
        LeadRepository $leadRepository,
        MergeRecordRepository $mergeRecordRepository,
        RequestStack $requestStack
    ) {
        $this->cookieHelper          = $cookieHelper;
        $this->leadDeviceRepository  = $leadDeviceRepository;
        $this->leadRepository        = $leadRepository;
        $this->mergeRecordRepository = $mergeRecordRepository;
        $this->requestStack          = $requestStack;
    }

    /**
     * @return Lead|null
     */
    public function getTrackedLead()
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return null;
        }

        $trackingId = $this->getTrackedIdentifier();
        if (null === $trackingId) {
            return null;
        }

        $leadId = $this->cookieHelper->getCookie($trackingId, null);
        if (null === $leadId) {
            $leadId = $request->get('mtc_id', null);
            if (null === $leadId) {
                return null;
            }
        }

        $lead = $this->leadRepository->getEntity($leadId);
        if (null === $lead) {
            // Check if this contact was merged into another and if so, return the new contact
            $lead = $this->mergeRecordRepository->findMergedContact($leadId);

            if (null === $lead) {
                return null;
            }

            // Hydrate fields with custom field data
            $fields = $this->leadRepository->getFieldValues($lead->getId());
            $lead->setFields($fields);
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
