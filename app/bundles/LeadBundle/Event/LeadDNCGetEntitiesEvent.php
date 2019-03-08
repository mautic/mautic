<?php
/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class LeadDNCGetEntitiesEvent.
 */
class LeadDNCGetEntitiesEvent extends CommonEvent
{
    /**
     * @var Lead
     */
    protected $lead;

    /**
     * @var string
     */
    protected $channel;

    /**
     * @var array
     */
    protected $coreEntities;

    /**
     * @var array
     */
    protected $pluginEntities;

    /**
     * LeadDNCGetEntitiesEvent constructor.
     *
     * @param Lead   $lead
     * @param string $channel
     * @param array  $dncEntities
     */
    public function __construct(Lead $lead, $channel, array $dncEntities = [])
    {
        $this->lead           = $lead;
        $this->channel        = $channel;
        $this->coreEntities   = $dncEntities;
        $this->pluginEntities = [];
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * Returns the array of DoNotContact entities.
     *
     * @return array
     */
    public function getDNCEntities()
    {
        return array_merge($this->coreEntities, $this->pluginEntities);
    }

    /**
     * @param array $dncEntities
     *
     * @return $this
     */
    public function addDNCEntities(array $dncEntities)
    {
        $this->pluginEntities = array_merge($this->pluginEntities, $dncEntities);

        return $this;
    }
}
