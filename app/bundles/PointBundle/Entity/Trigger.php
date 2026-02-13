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
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Mautic\ProjectBundle\Entity\ProjectTrait;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

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
        'groups'                  => ['trigger:read'],
        'swagger_definition_name' => 'Read',
        'api_included'            => ['category', 'events'],
    ],
    denormalizationContext: [
        'groups'                  => ['trigger:write'],
        'swagger_definition_name' => 'Write',
    ]
)]
class Trigger extends FormEntity implements UuidInterface
{
    use UuidTrait;
    use ProjectTrait;
    public const ENTITY_NAME = 'point_trigger';

    /**
     * @var int
     */
    #[Groups(['trigger:read'])]
    private $id;

    /**
     * @var string
     */
    #[Groups(['trigger:read', 'trigger:write'])]
    private $name;

    /**
     * @var string|null
     */
    #[Groups(['trigger:read', 'trigger:write'])]
    private $description;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['trigger:read', 'trigger:write'])]
    private $publishUp;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['trigger:read', 'trigger:write'])]
    private $publishDown;

    /**
     * @var int
     */
    #[Groups(['trigger:read', 'trigger:write'])]
    private $points = 0;

    /**
     * @var string
     */
    #[Groups(['trigger:read', 'trigger:write'])]
    private $color = 'a0acb8';

    /**
     * @var bool
     */
    #[Groups(['trigger:read', 'trigger:write'])]
    private $triggerExistingLeads = false;

    /**
     * @var Category|null
     **/
    #[Groups(['trigger:read', 'trigger:write'])]
    private $category;

    /**
     * @var ArrayCollection<int, TriggerEvent>
     */
    #[Groups(['trigger:read', 'trigger:write'])]
    private $events;

    #[Groups(['trigger:read', 'trigger:write'])]
    private ?Group $group = null;

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->initializeProjects();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('point_triggers')
            ->setCustomRepositoryClass(TriggerRepository::class);

        $builder->addIdColumns();

        $builder->addPublishDates();

        $builder->addField('points', 'integer');

        $builder->createField('color', 'string')
            ->length(7)
            ->build();

        $builder->createField('triggerExistingLeads', 'boolean')
            ->columnName('trigger_existing_leads')
            ->build();

        $builder->addCategory();

        $builder->createOneToMany('events', 'TriggerEvent')
            ->setIndexBy('id')
            ->setOrderBy(['order' => 'ASC'])
            ->mappedBy('trigger')
            ->cascadeAll()
            ->fetchExtraLazy()
            ->build();

        $builder->createManyToOne('group', Group::class)
            ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
            ->build();

        static::addUuidField($builder);
        self::addProjectsField($builder, 'point_trigger_projects_xref', 'point_trigger_id');
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank([
            'message' => 'mautic.core.name.required',
        ]));
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('trigger')
            ->addListProperties(
                [
                    'id',
                    'name',
                    'category',
                    'description',
                ]
            )
            ->addProperties(
                [
                    'publishUp',
                    'publishDown',
                    'points',
                    'color',
                    'events',
                    'triggerExistingLeads',
                ]
            )
            ->build();

        self::addProjectsInLoadApiMetadata($metadata, 'trigger');
    }

    /**
     * @param string $prop
     * @param mixed  $val
     */
    protected function isChanged($prop, $val)
    {
        if ('events' == $prop) {
            // changes are already computed so just add them
            $this->changes[$prop][$val[0]] = $val[1];
        } else {
            parent::isChanged($prop, $val);
        }
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
     * Set description.
     *
     * @param string $description
     *
     * @return Trigger
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
     * @return Trigger
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
     * Add events.
     *
     * @return Point
     */
    public function addTriggerEvent($key, TriggerEvent $event)
    {
        if ($changes = $event->getChanges()) {
            $this->isChanged('events', [$key, $changes]);
        }
        $this->events[$key] = $event;

        return $this;
    }

    /**
     * Remove events.
     */
    public function removeTriggerEvent(TriggerEvent $event): void
    {
        $this->events->removeElement($event);
    }

    /**
     * Get events.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * Set publishUp.
     *
     * @param \DateTime $publishUp
     *
     * @return Point
     */
    public function setPublishUp($publishUp)
    {
        $this->isChanged('publishUp', $publishUp);
        $this->publishUp = $publishUp;

        return $this;
    }

    /**
     * Get publishUp.
     *
     * @return \DateTimeInterface
     */
    public function getPublishUp()
    {
        return $this->publishUp;
    }

    /**
     * Set publishDown.
     *
     * @param \DateTime $publishDown
     *
     * @return Point
     */
    public function setPublishDown($publishDown)
    {
        $this->isChanged('publishDown', $publishDown);
        $this->publishDown = $publishDown;

        return $this;
    }

    /**
     * Get publishDown.
     *
     * @return \DateTimeInterface
     */
    public function getPublishDown()
    {
        return $this->publishDown;
    }

    /**
     * @return mixed
     */
    public function getPoints()
    {
        return $this->points;
    }

    /**
     * @param mixed $points
     */
    public function setPoints($points): void
    {
        $this->isChanged('points', $points);
        $this->points = $points;
    }

    /**
     * @return mixed
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param mixed $color
     */
    public function setColor($color): void
    {
        $this->color = $color;
    }

    /**
     * @return mixed
     */
    public function getTriggerExistingLeads()
    {
        return $this->triggerExistingLeads;
    }

    /**
     * @param mixed $triggerExistingLeads
     */
    public function setTriggerExistingLeads($triggerExistingLeads): void
    {
        $this->triggerExistingLeads = $triggerExistingLeads;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param mixed $category
     */
    public function setCategory($category): void
    {
        $this->category = $category;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    public function setGroup(Group $group): void
    {
        $this->group = $group;
    }
}
