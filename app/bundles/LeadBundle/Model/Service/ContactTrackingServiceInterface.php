<?php

namespace Mautic\LeadBundle\Model\Service;

use Mautic\LeadBundle\Entity\Lead;

/**
 * Interface ContactTrackingInterface.
 */
interface ContactTrackingServiceInterface
{
    /**
     * @return bool
     */
    public function isTracked();

    /**
     * Return current tracked Lead.
     *
     * @return Lead|null
     */
    public function getTrackedLead();

    /**
     * @return string|null Unique identifier
     */
    public function getTrackedIdentifier();

    /**
     * Track Lead so it can be accessed by getCurrent in future request.
     *
     * @param Lead $lead
     * @param bool $replaceCurrent
     *
     * @return string Unique identifier
     */
    public function track(Lead $lead, $replaceCurrent = false);
}
