<?php

namespace Mautic\PointBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('point:triggers:viewown')"),
        new Post(security: "is_granted('point:triggers:create')"),
        new Get(security: "is_granted('point:triggers:viewown')"),
        new Put(security: "is_granted('point:triggers:editown')"),
        new Patch(security: "is_granted('point:triggers:editother')"),
        new Delete(security: "is_granted('point:triggers:deleteown')"),
    ],
    normalizationContext: [
        'groups'                  => ['trigger_event:read'],
        'swagger_definition_name' => 'Read',
    ],
    denormalizationContext: [
        'groups'                  => ['trigger_event:write'],
        'swagger_definition_name' => 'Write',
    ]
)]
class TriggerEvent implements UuidInterface
{
    use UuidTrait;

    /**
     * @var int|null
     */
    #[Groups(['trigger_event:read'])]
    private $id;

    /**
     * @var string
     */
    #[Groups(['trigger_event:read', 'trigger_event:write'])]
    private $name;

    /**
     * @var string|null
     */
    #[Groups(['trigger_event:read', 'trigger_event:write'])]
    private $description;

    /**
     * @var string
     */
    #[Groups(['trigger_event:read', 'trigger_event:write'])]
    private $type;

    /**
     * @var int
     */
    #[Groups(['trigger_event:read', 'trigger_event:write'])]
    private $order = 0;

    /**
     * @var array
     */
    #[Groups(['trigger_event:read', 'trigger_event:write'])]
    private $properties = [];

    /**
     * @var Trigger
     */
    #[Groups(['trigger_event:read', 'trigger_event:write'])]
    private $trigger;

    /**
     * @var ArrayCollection<int,LeadTriggerLog>
     */
    private $log;

    /**
     * @var array
     */
    private $changes;

    public function __clone(): void
    {
        $this->id = null;
    }

    public function __construct()
    {
        $this->log = new ArrayCollection();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('point_trigger_events')
            ->setCustomRepositoryClass(TriggerEventRepository::class)
            ->addIndex(['type'], 'trigger_type_search');

        $builder->addIdColumns();

        $builder->createField('type', 'string')
            ->length(50)
            ->build();

        $builder->createField('order', 'integer')
            ->columnName('action_order')
            ->build();

        $builder->addField('properties', 'array');

        $builder->createManyToOne('trigger', 'Trigger')
            ->inversedBy('events')
            ->addJoinColumn('trigger_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->createOneToMany('log', 'LeadTriggerLog')
            ->mappedBy('event')
            ->cascadePersist()
            ->cascadeRemove()
            ->fetchExtraLazy()
            ->build();

        static::addUuidField($builder);
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('trigger')
            ->addProperties(
                [
                    'id',
                    'name',
                    'description',
                    'type',
                    'order',
                    'properties',
                ]
            )
            ->build();
    }

    private function isChanged($prop, $val): void
    {
        if ($this->$prop != $val) {
            $this->changes[$prop] = [$this->$prop, $val];
        }
    }

    /**
     * @return array
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $order
     *
     * @return TriggerEvent
     */
    public function setOrder($order)
    {
        $this->isChanged('order', $order);

        $this->order = $order;

        return $this;
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param array $properties
     *
     * @return TriggerEvent
     */
    public function setProperties($properties)
    {
        $this->isChanged('properties', $properties);

        $this->properties = $properties;

        return $this;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return self
     */
    public function setTrigger(Trigger $trigger)
    {
        $this->trigger = $trigger;

        return $this;
    }

    /**
     * @return Trigger
     */
    public function getTrigger()
    {
        return $this->trigger;
    }

    /**
     * @param string $type
     *
     * @return TriggerEvent
     */
    public function setType($type)
    {
        $this->isChanged('type', $type);
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    public function convertToArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * @param string $description
     *
     * @return TriggerEvent
     */
    public function setDescription($description)
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $name
     *
     * @return TriggerEvent
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return self
     */
    public function addLog(LeadTriggerLog $log)
    {
        $this->log[] = $log;

        return $this;
    }

    public function removeLog(LeadTriggerLog $log): void
    {
        $this->log->removeElement($log);
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLog()
    {
        return $this->log;
    }
}
