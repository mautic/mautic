<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class LeadEventLog
 * @ORM\Table(name="campaign_lead_event_log")
 * @ORM\Entity(repositoryClass="Mautic\CampaignBundle\Entity\LeadEventLogRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class LeadEventLog
{

    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="Event", inversedBy="log")
     * @ORM\JoinColumn(onDelete="CASCADE")
     **/
    private $event;

    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="Mautic\LeadBundle\Entity\Lead")
     * @ORM\JoinColumn(onDelete="CASCADE")
     **/
    private $lead;

    /**
     * @ORM\ManyToOne(targetEntity="Campaign")
     * @ORM\JoinColumn(onDelete="CASCADE")
     **/
    private $campaign;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\CoreBundle\Entity\IpAddress", cascade={"merge", "persist"})
     **/
    private $ipAddress;

    /**
     * @ORM\Column(name="date_triggered", type="datetime", nullable=true)
     **/
    private $dateTriggered;

    /**
     * @ORM\Column(name="is_scheduled", type="boolean")
     */
    private $isScheduled = false;

    /**
     * @ORM\Column(name="trigger_date", type="datetime", nullable=true)
     */
    private $triggerDate;

    /**
     * @ORM\Column(name="system_triggered", type="boolean")
     */
    private $systemTriggered = false;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $metadata = array();

    /**
     * @ORM\Column(name="non_action_path_taken", type="boolean", nullable=true)
     */
    private $nonActionPathTaken = false;

    /**
     * @return \DateTime
     */
    public function getDateTriggered ()
    {
        return $this->dateTriggered;
    }

    /**
     * @param \DateTime $dateTriggered
     */
    public function setDateTriggered ($dateTriggered)
    {
        $this->dateTriggered = $dateTriggered;
    }

    /**
     * @return \Mautic\CoreBundle\Entity\IpAddress
     */
    public function getIpAddress ()
    {
        return $this->ipAddress;
    }

    /**
     * @param \Mautic\CoreBundle\Entity\IpAddress $ipAddress
     */
    public function setIpAddress ($ipAddress)
    {
        $this->ipAddress = $ipAddress;
    }

    /**
     * @return mixed
     */
    public function getLead ()
    {
        return $this->lead;
    }

    /**
     * @param mixed $lead
     */
    public function setLead ($lead)
    {
        $this->lead = $lead;
    }

    /**
     * @return mixed
     */
    public function getEvent ()
    {
        return $this->event;
    }

    /**
     * @param mixed $event
     */
    public function setEvent ($event)
    {
        $this->event = $event;
    }

    /**
     * @return bool
     */
    public function getIsScheduled ()
    {
        return $this->isScheduled;
    }

    /**
     * @param bool $isScheduled
     */
    public function setIsScheduled ($isScheduled)
    {
        $this->isScheduled = $isScheduled;
    }

    /**
     * @return mixed
     */
    public function getTriggerDate ()
    {
        return $this->triggerDate;
    }

    /**
     * @param mixed $triggerDate
     */
    public function setTriggerDate ($triggerDate)
    {
        $this->triggerDate = $triggerDate;
    }

    /**
     * @return mixed
     */
    public function getCampaign ()
    {
        return $this->campaign;
    }

    /**
     * @param mixed $campaign
     */
    public function setCampaign ($campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * @return bool
     */
    public function getSystemTriggered ()
    {
        return $this->systemTriggered;
    }

    /**
     * @param bool $systemTriggered
     */
    public function setSystemTriggered ($systemTriggered)
    {
        $this->systemTriggered = $systemTriggered;
    }

    /**
     * @return mixed
     */
    public function getNonActionPathTaken()
    {
        return $this->nonActionPathTaken;
    }

    /**
     * @param mixed $nonActionPathTaken
     */
    public function setNonActionPathTaken($nonActionPathTaken)
    {
        $this->nonActionPathTaken = $nonActionPathTaken;
    }

    /**
     * @return mixed
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param mixed $metatdata
     */
    public function setMetadata($metadata)
    {
        if (!is_array($metadata)) {
            // Assumed output for timeline
            $metadata = array('timeline' => $metadata);
        }

        $this->metadata = $metadata;
    }
}
