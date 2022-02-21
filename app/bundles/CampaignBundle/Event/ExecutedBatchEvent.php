<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Event;

use Doctrine\Common\Collections\Collection;
use Mautic\CampaignBundle\Entity\LeadEventLog;

class ExecutedBatchEvent extends AbstractLogCollectionEvent
{
    /**
     * @return Collection<int, LeadEventLog>
     */
    public function getExecuted()
    {
        return $this->logs;
    }
}
