<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Entity\FormEntity;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as Serializer;
use Mautic\CoreBundle\Entity\IpAddress;

/**
 * Class Lead
 * @ORM\Table(name="leads")
 * @ORM\Entity(repositoryClass="Mautic\LeadBundle\Entity\LeadRepository")
 * @ORM\HasLifecycleCallbacks
 * @Serializer\XmlRoot("lead")
 * @Serializer\ExclusionPolicy("all")
 */
class Lead extends FormEntity
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"leadDetails", "leadList"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"leadDetails"})
     */
    private $owner;

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"leadDetails", "leadList"})
     */
    private $points = 0;

    /**
     * @ORM\OneToMany(targetEntity="PointsChangeLog", mappedBy="lead", cascade={"all"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"dateAdded" = "DESC"})
     */
    private $pointsChangeLog;

    /**
     * @ORM\ManyToMany(targetEntity="Mautic\CoreBundle\Entity\IpAddress", cascade={"merge", "persist", "detach"}, indexBy="ipAddress")
     * @ORM\JoinTable(name="lead_ips_xref",
     *   joinColumns={@ORM\JoinColumn(name="lead_id", referencedColumnName="id")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="ip_id", referencedColumnName="id")}
     * )
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"leadDetails"})
     */
    private $ipAddresses;

    /**
     * @ORM\Column(type="datetime", name="last_active", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"leadDetails", "leadList"})
     */
    private $lastActive;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $internal = array();

    /**
     * @ORM\Column(type="array", name="social_cache", nullable=true)
     */
    private $socialCache = array();

    /**
     * Just a place to store updated field values so we don't have to loop through them again comparing
     *
     * @var array
     */
    private $updatedFields = array();

    /**
     * Used to populate trigger color
     *
     * @var
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"leadDetails", "leadList"})
     */
    private $color;

    /**
     * Sets if the IP was just created by LeadModel::getCurrentLead()
     *
     * @var bool
     */
    private $newlyCreated = false;

    /**
     * @ORM\Column(name="date_identified", type="datetime", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"leadDetails"})
     */
    private $dateIdentified;

    /**
     * @ORM\OneToMany(targetEntity="LeadNote", mappedBy="lead", orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"dateAdded" = "DESC"})
     */
    private $notes;

    /**
     * Used by Mautic to populate the fields pulled from the DB
     * @var array
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"leadDetails", "leadList"})
     */
    protected $fields = array();

    /**
     * @ORM\Column(name="preferred_profile_image",type="string", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $preferredProfileImage;

    /**
     * Changed to true if the lead was anonymous before updating fields
     *
     * @var null
     */
    private $wasAnonymous = null;

    public $imported = false;

    protected function isChanged($prop, $val)
    {
        $getter  = "get" . ucfirst($prop);
        $current = $this->$getter();
        if ($prop == 'owner') {
            if ($current && !$val) {
                $this->changes['owner'] = array($current->getName() . ' ('. $current->getId().')', $val);
            } elseif (!$current && $val) {
                $this->changes['owner'] = array($current, $val->getName() . ' ('. $val->getId().')');
            } elseif ($current && $val && $current->getId() != $val->getId()) {
                $this->changes['owner'] = array($current->getName() . '('. $current->getId().')',
                    $val->getName() . '('. $val->getId().')');
            }
        } elseif ($prop == 'ipAddresses') {
            $this->changes['ipAddresses'] = array('', $val->getIpAddress());
        } elseif ($this->$getter() != $val) {
            $this->changes[$prop] = array($this->$getter(), $val);
        }
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->ipAddresses = new ArrayCollection();
    }

    /**
     * @return array
     */
    public function convertToArray()
    {
        return get_object_vars($this);
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return Lead
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * Set owner
     *
     * @param \Mautic\UserBundle\Entity\User $owner
     * @return Lead
     */
    public function setOwner(\Mautic\UserBundle\Entity\User $owner = null)
    {
        $this->isChanged('owner', $owner);
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner
     *
     * @return \Mautic\UserBundle\Entity\User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Add ipAddresses
     *
     * @param \Mautic\CoreBundle\Entity\IpAddress $ipAddresses
     * @return Lead
     */
    public function addIpAddress(\Mautic\CoreBundle\Entity\IpAddress $ipAddresses)
    {
        $ip = $ipAddresses->getIpAddress();
        if (!isset($this->ipAddresses[$ip])) {
            $this->isChanged('ipAddresses', $ipAddresses);
            $this->ipAddresses[$ip] = $ipAddresses;
        }

        return $this;
    }

    /**
     * Remove ipAddresses
     *
     * @param \Mautic\CoreBundle\Entity\IpAddress $ipAddresses
     */
    public function removeIpAddress(\Mautic\CoreBundle\Entity\IpAddress $ipAddresses)
    {
        $this->ipAddresses->removeElement($ipAddresses);
    }

    /**
     * Get ipAddresses
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getIpAddresses()
    {
        return $this->ipAddresses;
    }

    /**
     * Get full name
     *
     * @param bool $lastFirst
     * @return string
     */
    public function getName($lastFirst = false)
    {
        $firstName = (isset($this->fields['core']['firstname']['value'])) ? $this->fields['core']['firstname']['value'] : '';
        $lastName  = (isset($this->fields['core']['lastname']['value'])) ? $this->fields['core']['lastname']['value'] : '';
        $fullName = "";
        if ($lastFirst && !empty($firstName) && !empty($lastName)) {
            $fullName = $lastName . ", " . $firstName;
        } elseif (!empty($firstName) && !empty($lastName)) {
            $fullName = $firstName . " " . $lastName;
        } elseif (!empty($firstName)) {
            $fullName = $firstName;
        } elseif (!empty($lastName)) {
            $fullName = $lastName;
        }

        return $fullName;
    }

    /**
     * Get the primary identifier for the lead
     *
     * @param bool $lastFirst
     * @return string
     */
    public function getPrimaryIdentifier($lastFirst = false)
    {
        if ($name = $this->getName($lastFirst)) {
            return $name;
        } elseif (!empty($this->fields['core']['company']['value'])) {
            return $this->fields['core']['company']['value'];
        } elseif (!empty($this->fields['core']['email']['value'])) {
            return $this->fields['core']['email']['value'];
        } elseif (count($ips = $this->getIpAddresses())) {
            return $ips->first()->getIpAddress();
        } else {
            return 'mautic.lead.lead.anonymous';
        }
    }

    /**
     * Get the secondary identifier for the lead; mainly company
     *
     * @return string
     */
    public function getSecondaryIdentifier()
    {
        if (!empty($this->fields['core']['company']['value'])) {
            return $this->fields['core']['company']['value'];
        }

        return '';
    }

    /**
     * Get the location for the lead
     *
     * @return string
     */
    public function getLocation()
    {
        $location = '';

        if (!empty($this->fields['core']['city']['value'])) {
            $location .= $this->fields['core']['city']['value'] . ', ';
        }

        if (!empty($this->fields['core']['state']['value'])) {
            $location .= $this->fields['core']['state']['value'] . ', ';
        }

        if (!empty($this->fields['core']['country']['value'])) {
            $location .= $this->fields['core']['country']['value'] . ', ';
        }

        return rtrim($location, ', ');
    }

    /**
     * Adds/substracts from current points
     *
     * @param $points
     */
    public function addToPoints($points)
    {
        $newPoints = $this->points + $points;
        $this->setPoints($newPoints);
    }

    /**
     * Set points
     *
     * @param integer $points
     * @return Lead
     */
    public function setPoints($points)
    {
        $this->isChanged('points', $points);
        $this->points = $points;

        return $this;
    }

    /**
     * Get points
     *
     * @return integer
     */
    public function getPoints()
    {
        return $this->points;
    }

    /**
     * Creates a points change entry
     *
     * @param $type
     * @param $name
     * @param $action
     * @param $pointsDelta
     * @param IpAddress $ip
     */
    public function addPointsChangeLogEntry($type, $name, $action, $pointsDelta, IpAddress $ip)
    {
        if ($pointsDelta <= 0) {
            // No need to record this

            return;
        }

        //create a new points change event
        $event = new PointsChangeLog();
        $event->setType($type);
        $event->setEventName($name);
        $event->setActionName($action);
        $event->setDateAdded(new \DateTime());
        $event->setDelta($pointsDelta);
        $event->setIpAddress($ip);
        $event->setLead($this);
        $this->addPointsChangeLog($event);
    }

    /**
     * Add pointsChangeLog
     *
     * @param \Mautic\LeadBundle\Entity\PointsChangeLog $pointsChangeLog
     * @return Lead
     */
    public function addPointsChangeLog(\Mautic\LeadBundle\Entity\PointsChangeLog $pointsChangeLog)
    {
        $this->pointsChangeLog[] = $pointsChangeLog;

        return $this;
    }

    /**
     * Remove pointsChangeLog
     *
     * @param \Mautic\LeadBundle\Entity\PointsChangeLog $pointsChangeLog
     */
    public function removePointsChangeLog(\Mautic\LeadBundle\Entity\PointsChangeLog $pointsChangeLog)
    {
        $this->pointsChangeLog->removeElement($pointsChangeLog);
    }

    /**
     * Get pointsChangeLog
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPointsChangeLog()
    {
        return $this->pointsChangeLog;
    }

    /**
     * Set internal storage
     *
     * @param $internal
     */
    public function setInternal($internal)
    {
        $this->internal = $internal;
    }

    /**
     * Get internal storage
     *
     * @return mixed
     */
    public function getInternal()
    {
        return $this->internal;
    }

    /**
     * Set social cache
     *
     * @param $cache
     */
    public function setSocialCache($cache)
    {
        $this->socialCache = $cache;
    }

    /**
     * Get social cache
     *
     * @return mixed
     */
    public function getSocialCache()
    {
        return $this->socialCache;
    }

    /**
     * @param $fields
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    /**
     * @return array
     */
    public function getFields($ungroup = false)
    {
        if ($ungroup && isset($this->fields['core'])) {
            $return = array();
            foreach ($this->fields as $group => $fields) {
                $return += $fields;
            }
            return $return;
        }
        return $this->fields;
    }

    /**
     * Add an updated field to persist to the DB and to note changes
     *
     * @param $alias
     * @param $value
     */
    public function addUpdatedField($alias, $value, $oldValue = '')
    {
        if ($this->wasAnonymous == null) {
            $this->wasAnonymous = $this->isAnonymous();
        }
        $this->changes['fields'][$alias] = array($oldValue, $value);
        $this->updatedFields[$alias]     = $value;
    }

    /**
     * Get the array of updated fields
     *
     * @return array
     */
    public function getUpdatedFields()
    {
        return $this->updatedFields;
    }

    /**
     * @return mixed
     */
    public function getColor ()
    {
        return $this->color;
    }

    /**
     * @param mixed $color
     */
    public function setColor ($color)
    {
        $this->color = $color;
    }

    /**
     * @return bool
     */
    public function isAnonymous ()
    {
        if ($name = $this->getName() || !empty($this->fields['core']['company']['value']) || !empty($this->fields['core']['email']['value'])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @return boolean
     */
    public function isNewlyCreated ()
    {
        return $this->newlyCreated;
    }

    /**
     * @param boolean $newlyCreated
     */
    public function setNewlyCreated ($newlyCreated)
    {
        $this->newlyCreated = $newlyCreated;
    }

    /**
     * @return mixed
     */
    public function getNotes ()
    {
        return $this->notes;
    }

    /**
     * @param string $source
     *
     * @return void
     */
    public function setPreferredProfileImage($source)
    {
        $this->preferredProfileImage = $source;
    }

    /**
     * @return string
     */
    public function getPreferredProfileImage()
    {
        return $this->preferredProfileImage;
    }

    /**
     * @return mixed
     */
    public function getDateIdentified ()
    {
        return $this->dateIdentified;
    }

    /**
     * @param mixed $dateIdentified
     */
    public function setDateIdentified ($dateIdentified)
    {
        $this->dateIdentified = $dateIdentified;
    }

    /**
     * @ORM\PreUpdate
     * @ORM\PrePersist
     */
    public function checkDateIdentified()
    {
        if ($this->dateIdentified == null && $this->wasAnonymous) {
            //check the changes to see if the user is now known
            if (!$this->isAnonymous()) {
                $this->dateIdentified = new \DateTime();
                $this->changes['dateIdentified'] = array('', $this->dateIdentified);
            }
        }
    }

    /**
     * @return mixed
     */
    public function getLastActive ()
    {
        return $this->lastActive;
    }

    /**
     * @param mixed $lastActive
     */
    public function setLastActive ($lastActive)
    {
        $this->lastActive = $lastActive;
    }
}
