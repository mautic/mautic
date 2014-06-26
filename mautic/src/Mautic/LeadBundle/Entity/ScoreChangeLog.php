<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class ScoreChangeLog
 * @ORM\Table(name="lead_score_change_log")
 * @ORM\Entity
 */
class ScoreChangeLog
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Lead", inversedBy="scoreChangeLog")
     * @ORM\JoinColumn(name="lead_id", referencedColumnName="id", nullable=false)
     */
    private $lead;


    /**
     * @ORM\ManyToOne(targetEntity="Mautic\CoreBundle\Entity\IpAddress", cascade={"merge", "persist", "refresh", "detach"})
     * @ORM\JoinColumn(name="ip_id", referencedColumnName="id", nullable=false)
     */
    private $ipAddress;

    /**
     * @ORM\Column(type="text", length=50)
     */
    private $type;

    /**
     * @ORM\Column(name="event_name", type="text", length=255)
     */
    private $eventName;

    /**
     * @ORM\Column(name="action_name", type="text", length=255)
     */
    private $actionName;

    /**
     * @ORM\Column(type="integer")
     */
    private $delta;

    /**
     * @ORM\Column(name="date_added", type="datetime")
     */
    private $dateAdded;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return ScoreChangeLog
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set eventName
     *
     * @param string $eventName
     * @return ScoreChangeLog
     */
    public function setEventName($eventName)
    {
        $this->eventName = $eventName;

        return $this;
    }

    /**
     * Get eventName
     *
     * @return string 
     */
    public function getEventName()
    {
        return $this->eventName;
    }

    /**
     * Set actionName
     *
     * @param string $actionName
     * @return ScoreChangeLog
     */
    public function setActionName($actionName)
    {
        $this->actionName = $actionName;

        return $this;
    }

    /**
     * Get actionName
     *
     * @return string 
     */
    public function getActionName()
    {
        return $this->actionName;
    }

    /**
     * Set delta
     *
     * @param integer $delta
     * @return ScoreChangeLog
     */
    public function setDelta($delta)
    {
        $this->delta = $delta;

        return $this;
    }

    /**
     * Get delta
     *
     * @return integer 
     */
    public function getDelta()
    {
        return $this->delta;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return ScoreChangeLog
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * Get dateAdded
     *
     * @return \DateTime 
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * Set lead
     *
     * @param \Mautic\LeadBundle\Entity\Lead $lead
     * @return ScoreChangeLog
     */
    public function setLead(\Mautic\LeadBundle\Entity\Lead $lead)
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * Get lead
     *
     * @return \Mautic\LeadBundle\Entity\Lead 
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * Set ipAddress
     *
     * @param \Mautic\CoreBundle\Entity\IpAddress $ipAddress
     * @return ScoreChangeLog
     */
    public function setIpAddress(\Mautic\CoreBundle\Entity\IpAddress $ipAddress)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Get ipAddress
     *
     * @return \Mautic\CoreBundle\Entity\IpAddress 
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }
}
