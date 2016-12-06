<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Class Event.
 */
class Event
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $eventType;

    /**
     * @var int
     */
    private $order = 0;

    /**
     * @var array
     */
    private $properties = [];

    /**
     * @var null|\DateTime
     */
    private $triggerDate;

    /**
     * @var int
     */
    private $triggerInterval = 0;

    /**
     * @var string
     */
    private $triggerIntervalUnit;

    /**
     * @var string
     */
    private $triggerMode;

    /**
     * @var Campaign
     */
    private $campaign;

    /**
     * @var ArrayCollection
     **/
    private $children;

    /**
     * @var Event
     **/
    private $parent = null;

    /**
     * @var string
     **/
    private $decisionPath;

    /**
     * @var string
     **/
    private $tempId;

    /**
     * @var ArrayCollection
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

    /**
     * Clean up after clone.
     */
    public function __clone()
    {
        $this->id       = null;
        $this->tempId   = null;
        $this->campaign = null;
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('campaign_events')
            ->setCustomRepositoryClass('Mautic\CampaignBundle\Entity\EventRepository')
            ->addIndex(['type', 'event_type'], 'campaign_event_type_search')
            ->addIndex(['event_type'], 'event_type');

        $builder->addIdColumns();

        $builder->createField('type', 'string')
            ->length(50)
            ->build();

        $builder->createField('eventType', 'string')
            ->columnName('event_type')
            ->length(50)
            ->build();

        $builder->createField('order', 'integer')
            ->columnName('event_order')
            ->build();

        $builder->addField('properties', 'array');

        $builder->createField('triggerDate', 'datetime')
            ->columnName('trigger_date')
            ->nullable()
            ->build();

        $builder->createField('triggerInterval', 'integer')
            ->columnName('trigger_interval')
            ->nullable()
            ->build();

        $builder->createField('triggerIntervalUnit', 'string')
            ->columnName('trigger_interval_unit')
            ->length(1)
            ->nullable()
            ->build();

        $builder->createField('triggerMode', 'string')
            ->columnName('trigger_mode')
            ->length(10)
            ->nullable()
            ->build();

        $builder->createManyToOne('campaign', 'Campaign')
            ->inversedBy('events')
            ->addJoinColumn('campaign_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->createOneToMany('children', 'Event')
            ->setIndexBy('id')
            ->setOrderBy(['order' => 'ASC'])
            ->mappedBy('parent')
            ->build();

        $builder->createManyToOne('parent', 'Event')
            ->inversedBy('children')
            ->cascadePersist()
            ->addJoinColumn('parent_id', 'id')
            ->build();

        $builder->createField('decisionPath', 'string')
            ->columnName('decision_path')
            ->nullable()
            ->build();

        $builder->createField('tempId', 'string')
            ->columnName('temp_id')
            ->nullable()
            ->build();

        $builder->createOneToMany('log', 'LeadEventLog')
            ->mappedBy('event')
            ->cascadePersist()
            ->cascadeRemove()
            ->fetchExtraLazy()
            ->build();
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('campaign')
            ->addProperties(
                [
                    'id',
                    'name',
                    'description',
                    'type',
                    'eventType',
                    'order',
                    'properties',
                    'triggerDate',
                    'triggerInterval',
                    'triggerIntervalUnit',
                    'triggerMode',
                    'children',
                    'parent',
                    'decisionPath',
                ]
            )
            ->setMaxDepth(1, 'parent')
            ->build();
    }

    /**
     * @param string $prop
     * @param mixed  $val
     */
    private function isChanged($prop, $val)
    {
        $getter  = 'get'.ucfirst($prop);
        $current = $this->$getter();
        if ($prop == 'category' || $prop == 'parent') {
            $currentId = ($current) ? $current->getId() : '';
            $newId     = ($val) ? $val->getId() : null;
            if ($currentId != $newId) {
                $this->changes[$prop] = [$currentId, $newId];
            }
        } elseif ($this->$prop != $val) {
            $this->changes[$prop] = [$this->$prop, $val];
        }
    }

    /**
     * @return mixed
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set order.
     *
     * @param int $order
     *
     * @return Event
     */
    public function setOrder($order)
    {
        $this->isChanged('order', $order);

        $this->order = $order;

        return $this;
    }

    /**
     * Get order.
     *
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set properties.
     *
     * @param array $properties
     *
     * @return Event
     */
    public function setProperties($properties)
    {
        $this->isChanged('properties', $properties);

        $this->properties = $properties;

        return $this;
    }

    /**
     * Get properties.
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Set campaign.
     *
     * @param \Mautic\CampaignBundle\Entity\Campaign $campaign
     *
     * @return Event
     */
    public function setCampaign(\Mautic\CampaignBundle\Entity\Campaign $campaign)
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * Get campaign.
     *
     * @return \Mautic\CampaignBundle\Entity\Campaign
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return Event
     */
    public function setType($type)
    {
        $this->isChanged('type', $type);
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
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
     * Set description.
     *
     * @param string $description
     *
     * @return Event
     */
    public function setDescription($description)
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Event
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add log.
     *
     * @param LeadEventLog $log
     *
     * @return Event
     */
    public function addLog(LeadEventLog $log)
    {
        $this->log[] = $log;

        return $this;
    }

    /**
     * Remove log.
     *
     * @param LeadEventLog $log
     */
    public function removeLog(LeadEventLog $log)
    {
        $this->log->removeElement($log);
    }

    /**
     * Get log.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * Add children.
     *
     * @param \Mautic\CampaignBundle\Entity\Event $children
     *
     * @return Event
     */
    public function addChild(\Mautic\CampaignBundle\Entity\Event $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children.
     *
     * @param \Mautic\CampaignBundle\Entity\Event $children
     */
    public function removeChild(\Mautic\CampaignBundle\Entity\Event $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set parent.
     *
     * @param \Mautic\CampaignBundle\Entity\Event $parent
     *
     * @return Event
     */
    public function setParent(\Mautic\CampaignBundle\Entity\Event $parent = null)
    {
        $this->isChanged('parent', $parent);
        $this->parent = $parent;

        return $this;
    }

    /**
     * Remove parent.
     */
    public function removeParent()
    {
        $this->isChanged('parent', '');
        $this->parent = null;
    }

    /**
     * Get parent.
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
    public function getTriggerDate()
    {
        return $this->triggerDate;
    }

    /**
     * @param mixed $triggerDate
     */
    public function setTriggerDate($triggerDate)
    {
        $this->isChanged('triggerDate', $triggerDate);
        $this->triggerDate = $triggerDate;
    }

    /**
     * @return int
     */
    public function getTriggerInterval()
    {
        return $this->triggerInterval;
    }

    /**
     * @param int $triggerInterval
     */
    public function setTriggerInterval($triggerInterval)
    {
        $this->isChanged('triggerInterval', $triggerInterval);
        $this->triggerInterval = $triggerInterval;
    }

    /**
     * @return mixed
     */
    public function getTriggerIntervalUnit()
    {
        return $this->triggerIntervalUnit;
    }

    /**
     * @param mixed $triggerIntervalUnit
     */
    public function setTriggerIntervalUnit($triggerIntervalUnit)
    {
        $this->isChanged('triggerIntervalUnit', $triggerIntervalUnit);
        $this->triggerIntervalUnit = $triggerIntervalUnit;
    }

    /**
     * @return mixed
     */
    public function getEventType()
    {
        return $this->eventType;
    }

    /**
     * @param mixed $eventType
     */
    public function setEventType($eventType)
    {
        $this->eventType = $eventType;
    }

    /**
     * @return mixed
     */
    public function getTriggerMode()
    {
        return $this->triggerMode;
    }

    /**
     * @param mixed $triggerMode
     */
    public function setTriggerMode($triggerMode)
    {
        $this->triggerMode = $triggerMode;
    }

    /**
     * @return mixed
     */
    public function getDecisionPath()
    {
        return $this->decisionPath;
    }

    /**
     * @param mixed $decisionPath
     */
    public function setDecisionPath($decisionPath)
    {
        $this->decisionPath = $decisionPath;
    }

    /**
     * @return mixed
     */
    public function getTempId()
    {
        return $this->tempId;
    }

    /**
     * @param mixed $tempId
     */
    public function setTempId($tempId)
    {
        $this->tempId = $tempId;
    }
}
