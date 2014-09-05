<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class TriggerEvent
 * @ORM\Table(name="point_trigger_events")
 * @ORM\Entity(repositoryClass="Mautic\PointBundle\Entity\TriggerEventRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class TriggerEvent
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $description;

    /**
     * @ORM\Column(name="action_order", type="integer")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $order = 0;

    /**
     * @ORM\Column(type="array")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $properties = array();

    /**
     * @ORM\ManyToOne(targetEntity="Trigger", inversedBy="events")
     * @ORM\JoinColumn(name="trigger_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $trigger;

    /**
     * @ORM\ManyToMany(targetEntity="Mautic\LeadBundle\Entity\Lead", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="point_trigger_event_lead_xref")
     * @ORM\JoinColumn(name="lead_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $leads;

    private $changes;

    private function isChanged($prop, $val)
    {
        if ($this->$prop != $val) {
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
     * @return TriggerEvent
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
     * @return TriggerEvent
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
     * Set trigger
     *
     * @param \Mautic\PointBundle\Entity\Trigger $trigger
     * @return TriggerTriggerEvent
     */
    public function setTrigger(\Mautic\PointBundle\Entity\Trigger $trigger)
    {
        $this->trigger = $trigger;

        return $this;
    }

    /**
     * Get trigger
     *
     * @return \Mautic\PointBundle\Entity\Trigger
     */
    public function getTrigger()
    {
        return $this->trigger;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return TriggerEvent
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
     * @return TriggerEvent
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
     * @return TriggerEvent
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
     * Add lead
     *
     * @param \Mautic\LeadBundle\Entity\Lead $lead
     * @return Lead
     */
    public function addLead(\Mautic\LeadBundle\Entity\Lead $lead)
    {
        $this->leads[] = $lead;

        return $this;
    }

    /**
     * Remove lead
     *
     * @param \Mautic\LeadBundle\Entity\Lead $lead
     */
    public function removeLead(\Mautic\LeadBundle\Entity\Lead $lead)
    {
        $this->leads->removeElement($lead);
    }

    /**
     * Get leads
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLeads()
    {
        return $this->leads;
    }
}
