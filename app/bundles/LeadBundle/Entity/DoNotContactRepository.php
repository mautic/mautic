<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Query;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * DoNotContactRepository
 */
class DoNotContactRepository extends CommonRepository
{
    /**
     * Get a list of DNC entries based on channel and lead_id
     *
     * @param Lead $lead
     * @param string $channel
     *
     * @return \Mautic\LeadBundle\Entity\DoNotContact[]
     */
    public function getEntriesByLeadAndChannel(Lead $lead, $channel)
    {
        return $this->findBy(array('channel' => $channel, 'lead' => $lead));
    }
}
