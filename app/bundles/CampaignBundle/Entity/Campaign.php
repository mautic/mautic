<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\LeadBundle\Form\Validator\Constraints\LeadListAccess;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class Campaign
 * @ORM\Table(name="campaigns")
 * @ORM\Entity(repositoryClass="Mautic\CampaignBundle\Entity\CampaignRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Campaign extends FormEntity
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"campaignDetails", "campaignList"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"campaignDetails", "campaignList"})
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"campaignDetails"})
     */
    private $description;

    /**
     * @ORM\Column(name="publish_up", type="datetime", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"campaignDetails"})
     */
    private $publishUp;

    /**
     * @ORM\Column(name="publish_down", type="datetime", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"campaignDetails"})
     */
    private $publishDown;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\CategoryBundle\Entity\Category")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"campaignDetails", "campaignList"})
     **/
    private $category;

    /**
     * @ORM\OneToMany(targetEntity="Event", mappedBy="campaign", cascade={"all"}, indexBy="id", fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"order" = "ASC"})
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"campaignDetails"})
     */
    private $events;

    /**
     * @ORM\OneToMany(targetEntity="Lead", mappedBy="campaign", indexBy="id", fetch="EXTRA_LAZY")
     */
    private $leads;

    /**
     * @ORM\ManyToMany(targetEntity="Mautic\LeadBundle\Entity\LeadList", fetch="EXTRA_LAZY", indexBy="id")
     * @ORM\JoinTable(name="campaign_leadlist_xref")
     * @ORM\JoinColumn(name="list_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     **/
    private $lists;

    /**
     * @ORM\Column(name="canvas_settings", type="array", nullable=true)
     */
    private $canvasSettings = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->leads  = new ArrayCollection();
        $this->lists  = new ArrayCollection();
    }

    /**
     *
     */
    public function __clone()
    {
        $this->leads  = new ArrayCollection();
        $this->id    = null;
    }

    /**
     * @return array
     */
    public function convertToArray()
    {
        return get_object_vars($this);
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank(array(
            'message' => 'mautic.core.name.required'
        )));

        $metadata->addPropertyConstraint('lists', new LeadListAccess(array(
            'message' => 'mautic.lead.lists.required'
        )));

        $metadata->addPropertyConstraint('lists', new Assert\NotBlank(array(
            'message' => 'mautic.lead.lists.required'
        )));
    }

    /**
     * @param string $prop
     * @param mixed  $val
     *
     * @return void
     */
    protected function isChanged($prop, $val)
    {
        $getter  = "get" . ucfirst($prop);
        $current = $this->$getter();
        if ($prop == 'category') {
            $currentId = ($current) ? $current->getId() : '';
            $newId     = ($val) ? $val->getId() : null;
            if ($currentId != $newId) {
                $this->changes[$prop] = array($currentId, $newId);
            }
        } elseif ($current != $val) {
            $this->changes[$prop] = array($current, $val);
        }
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
     * Set description
     *
     * @param string $description
     * @return Campaign
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
     * @return Campaign
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
     * Add events
     *
     * @param $key
     * @param \Mautic\CampaignBundle\Entity\Event $event
     * @return Campaign
     */
    public function addEvent($key, Event $event)
    {
        if ($changes = $event->getChanges()) {
            $this->changes['events']['added'][$key] = array($key, $changes);
        }
        $this->events[$key] = $event;

        return $this;
    }

    /**
     * Remove events
     *
     * @param \Mautic\CampaignBundle\Entity\Event $event
     */
    public function removeEvent(\Mautic\CampaignBundle\Entity\Event $event)
    {
        $this->changes['events']['removed'][$event->getId()] = $event->getName();

        $this->events->removeElement($event);
    }

    /**
     * Get events
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * Set publishUp
     *
     * @param \DateTime $publishUp
     * @return Campaign
     */
    public function setPublishUp($publishUp)
    {
        $this->isChanged('publishUp', $publishUp);
        $this->publishUp = $publishUp;

        return $this;
    }

    /**
     * Get publishUp
     *
     * @return \DateTime
     */
    public function getPublishUp()
    {
        return $this->publishUp;
    }

    /**
     * Set publishDown
     *
     * @param \DateTime $publishDown
     * @return Campaign
     */
    public function setPublishDown($publishDown)
    {
        $this->isChanged('publishDown', $publishDown);
        $this->publishDown = $publishDown;

        return $this;
    }

    /**
     * Get publishDown
     *
     * @return \DateTime
     */
    public function getPublishDown()
    {
        return $this->publishDown;
    }

    /**
     * @return mixed
     */
    public function getCategory ()
    {
        return $this->category;
    }

    /**
     * @param mixed $category
     */
    public function setCategory ($category)
    {
        $this->isChanged('category', $category);
        $this->category = $category;
    }

    /**
     * Add lead
     *
     * @param $key
     * @param \Mautic\CampaignBundle\Entity\Lead $lead
     * @return Campaign
     */
    public function addLead($key, Lead $lead)
    {
        $action     = ($this->leads->contains($lead)) ? 'updated' : 'added';
        $leadEntity = $lead->getLead();

        $this->changes['leads'][$action][$leadEntity->getId()] = $leadEntity->getPrimaryIdentifier();
        $this->leads[$key] = $lead;

        return $this;
    }

    /**
     * Remove lead
     *
     * @param Lead $lead
     */
    public function removeLead(Lead $lead)
    {
        $leadEntity = $lead->getLead();
        $this->changes['leads']['removed'][$leadEntity->getId()] = $leadEntity->getPrimaryIdentifier();
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

    /**
     * @return ArrayCollection
     */
    public function getLists()
    {
        return $this->lists;
    }

    /**
     * Add list
     *
     * @param \Mautic\LeadBundle\Entity\LeadList $list
     * @return Campaign
     */
    public function addList(\Mautic\LeadBundle\Entity\LeadList $list)
    {
        $this->lists[] = $list;

        $this->changes['lists']['added'][$list->getId()] = $list->getName();

        return $this;
    }

    /**
     * Remove list
     *
     * @param \Mautic\LeadBundle\Entity\LeadList $list
     */
    public function removeList(\Mautic\LeadBundle\Entity\LeadList $list)
    {
        $this->changes['lists']['removed'][$list->getId()] = $list->getName();
        $this->lists->removeElement($list);
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
}
