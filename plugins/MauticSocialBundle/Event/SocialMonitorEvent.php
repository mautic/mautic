<?php

namespace MauticPlugin\MauticSocialBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use MauticPlugin\MauticSocialBundle\Entity\Monitoring;

class SocialMonitorEvent extends CommonEvent
{
    /**
<<<<<<< HEAD
     * @var int
     */
    protected $newLeadCount = 0;
=======
     * @var string
     */
    protected $integrationName;

    protected int $newLeadCount;
>>>>>>> 11b4805f88 ([type-declarations] Re-run rector rules on plugins, Report, Sms, User, Lead, Dynamic, Config bundles)

    protected int $updatedLeadCount;

    protected array $leadIds;

    /**
     * @param string $integrationName
     * @param int    $newLeadCount
     * @param int    $updatedLeadCount
     */
    public function __construct(protected $integrationName, Monitoring $monitoring, array $leadIds, $newLeadCount, $updatedLeadCount)
    {
        $this->entity           = $monitoring;
        $this->leadIds          = $leadIds;
        $this->newLeadCount     = (int) $newLeadCount;
        $this->updatedLeadCount = (int) $updatedLeadCount;
    }

    /**
     * Returns the Monitoring entity.
     *
     * @return Monitoring
     */
    public function getMonitoring()
    {
        return $this->entity;
    }

    /**
     * Get count of new leads.
     *
     * @return int
     */
    public function getNewLeadCount()
    {
        return $this->newLeadCount;
    }

    /**
     * Get count of updated leads.
     *
     * @return int
     */
    public function getUpdatedLeadCount()
    {
        return $this->updatedLeadCount;
    }

    /**
     * @return array|int
     */
    public function getTotalLeadCount()
    {
        return $this->updatedLeadCount + $this->newLeadCount;
    }

    /**
     * @return array
     */
    public function getLeadIds()
    {
        return $this->leadIds;
    }

    /**
     * @return mixed
     */
    public function getIntegrationName()
    {
        return $this->integrationName;
    }
}
