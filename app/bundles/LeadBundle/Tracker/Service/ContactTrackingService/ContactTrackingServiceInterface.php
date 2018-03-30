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

use Mautic\LeadBundle\Entity\Lead;

/**
 * Interface ContactTrackingInterface.
 */
interface ContactTrackingServiceInterface
{
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
}
