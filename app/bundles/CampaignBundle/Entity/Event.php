<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Event
 * @ORM\Table(name="campaign_events")
 * @ORM\Entity(repositoryClass="Mautic\CampaignBundle\Entity\EventRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Event
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"campaignDetails"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"campaignDetails"})
     */
    private $type;

    /**
     * @ORM\Column(name="event_type", type="string", length=50)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"campaignDetails"})
     */
    private $eventType;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"campaignDetails"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"campaignDetails"})
     */
    private $description;

    /**
     * @ORM\Column(name="event_order", type="decimal", scale=2)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"campaignDetails"})
     */
    private $order = 0;

    /**
     * @ORM\Column(type="array")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"campaignDetails"})
     */
    private $properties = array();

    /**
     * @ORM\Column(name="trigger_date", type="datetime", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"campaignDetails"})
     */
    private $triggerDate;

    /**
     * @ORM\Column(name="trigger_interval", type="integer", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"campaignDetails"})
     */
    private $triggerInterval = 0;

    /**
     * @ORM\Column(name="trigger_interval_unit", type="string", length=1, nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"campaignDetails"})
     */
    private $triggerIntervalUnit;

    /**
     * @ORM\Column(name="trigger_mode", type="string", length=10, nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"campaignDetails"})
     */
    private $triggerMode;

    /**
     * @ORM\ManyToOne(targetEntity="Campaign", inversedBy="events")
     * @ORM\JoinColumn(name="campaign_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $campaign;

    /**
     * @ORM\OneToMany(targetEntity="Event", mappedBy="parent", indexBy="id")
     * @ORM\OrderBy({"order" = "ASC"})
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"campaignDetails"})
     **/
    private $children;

    /**
     * @ORM\ManyToOne(targetEntity="Event", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"campaignDetails"})
     * @Serializer\MaxDepth(1)
     **/
    private $parent = null;

    /**
     * @ORM\Column(name="canvas_settings", type="array", nullable=true)
     */
    private $canvasSettings = array();

    /**
     * @ORM\Column(name="decision_path", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"campaignDetails"})
     **/
    private $decisionPath;

    /**
     * @ORM\Column(name="temp_id", nullable=true)
     **/
    private $tempId;

    /**
     * @ORM\OneToMany(targetEntity="LeadEventLog", mappedBy="event", fetch="EXTRA_LAZY", cascade={"persist", "remove"})
     */
    private $log;

    /**
     * @var
     */
    private $changes;

    public function __construct()
    {
        $this->log      = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    private function isChanged($prop, $val)
    {
        $getter  = "get" . ucfirst($prop);
        $current = $this->$getter();
        if ($prop == 'category') {
            $currentId = ($current) ? $current->getId() : '';
            $newId     = ($val) ? $val->getId() : null;
            if ($currentId != $newId) {
                $this->changes[$prop] = array($currentId, $newId);
            }
        } elseif ($this->$prop != $val) {
            $this->changes[$prop] = array($this->$prop, $val);
        }
    }

    public function getChanges()
    {
        return $this->changes;
    }

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
     * Set order
     *
     * @param integer $order
     * @return Event
     */
    public function setOrder($order)
    {
        $this->isChanged('order', $order);

        $this->order = $order;

        return $this;
    }

    /**
     * Get order
     *
     * @return integer
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set properties
     *
     * @param array $properties
     * @return Event
     */
    public function setProperties($properties)
    {
        $this->isChanged('properties', $properties);

        $this->properties = $properties;

        return $this;
    }

    /**
     * Get properties
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Set campaign
     *
     * @param \Mautic\CampaignBundle\Entity\Campaign $campaign
     * @return Event
     */
    public function setCampaign(\Mautic\CampaignBundle\Entity\Campaign $campaign)
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * Get campaign
     *
     * @return \Mautic\CampaignBundle\Entity\Campaign
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return Event
     */
    public function setType($type)
    {
        $this->isChanged('type', $type);
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
     * @return array
     */
    public function convertToArray()
    {
        return get_object_vars($this);
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Event
     */
    public function setDescription($description)
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Event
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add log
     *
     * @param LeadEventLog $log
     * @return Log
     */
    public function addLog(LeadEventLog $log)
    {
        $this->log[] = $log;

        return $this;
    }

    /**
     * Remove log
     *
     * @param LeadEventLog $log
     */
    public function removeLog(LeadEventLog $log)
    {
        $this->log->removeElement($log);
    }

    /**
     * Get log
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * Add children
     *
     * @param \Mautic\CampaignBundle\Entity\Event $children
     * @return Event
     */
    public function addChild(\Mautic\CampaignBundle\Entity\Event $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param \Mautic\CampaignBundle\Entity\Event $children
     */
    public function removeChild(\Mautic\CampaignBundle\Entity\Event $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set parent
     *
     * @param \Mautic\CampaignBundle\Entity\Event $parent
     * @return Event
     */
    public function setParent(\Mautic\CampaignBundle\Entity\Event $parent = null)
    {
        $this->isChanged('parent', $parent);
        $this->parent = $parent;

        return $this;
    }

    /**
     * Remove parent
     */
    public function removeParent()
    {
        $this->isChanged('parent', '');
        $this->parent = null;
    }

    /**
     * Get parent
     *
     * @return \Mautic\CampaignBundle\Entity\Event
     */
    public function getParent()
    {
        return $this->parent;
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
        $this->isChanged('triggerDate', $triggerDate);
        $this->triggerDate = $triggerDate;
    }

    /**
     * @return mixed
     */
    public function getTriggerInterval ()
    {
        return $this->triggerInterval;
    }

    /**
     * @param mixed $triggerInterval
     */
    public function setTriggerInterval ($triggerInterval)
    {
        $this->isChanged('triggerInterval', $triggerInterval);
        $this->triggerInterval = $triggerInterval;
    }

    /**
     * @return mixed
     */
    public function getTriggerIntervalUnit ()
    {
        return $this->triggerIntervalUnit;
    }

    /**
     * @param mixed $triggerIntervalUnit
     */
    public function setTriggerIntervalUnit ($triggerIntervalUnit)
    {
        $this->isChanged('triggerIntervalUnit', $triggerIntervalUnit);
        $this->triggerIntervalUnit = $triggerIntervalUnit;
    }

    /**
     * @return mixed
     */
    public function getEventType ()
    {
        return $this->eventType;
    }

    /**
     * @param mixed $eventType
     */
    public function setEventType ($eventType)
    {
        $this->eventType = $eventType;
    }

    /**
     * @return mixed
     */
    public function getTriggerMode ()
    {
        return $this->triggerMode;
    }

    /**
     * @param mixed $triggerMode
     */
    public function setTriggerMode ($triggerMode)
    {
        $this->triggerMode = $triggerMode;
    }

    /**
     * @return mixed
     */
    public function getCanvasSettings ()
    {
        return $this->canvasSettings;
    }

    /**
     * @param array $canvasSettings
     */
    public function setCanvasSettings (array $canvasSettings)
    {
        $this->canvasSettings = $canvasSettings;
    }

    /**
     * @return mixed
     */
    public function getDecisionPath ()
    {
        return $this->decisionPath;
    }

    /**
     * @param mixed $decisionPath
     */
    public function setDecisionPath ($decisionPath)
    {
        $this->decisionPath = $decisionPath;
    }

    /**
     * @return mixed
     */
    public function getTempId ()
    {
        return $this->tempId;
    }

    /**
     * @param mixed $tempId
     */
    public function setTempId ($tempId)
    {
        $this->tempId = $tempId;
    }
}
