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
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Mautic\ProjectBundle\Entity\ProjectTrait;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('point:triggers:viewown')"),
        new Post(security: "is_granted('point:triggers:create')"),
        new Get(security: "is_granted('point:triggers:viewown', object)"),
        new Put(security: "is_granted('point:triggers:editown', object)"),
        new Patch(security: "is_granted('point:triggers:editother', object)"),
        new Delete(security: "is_granted('point:triggers:deleteown', object)"),
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
#[ORM\Entity(repositoryClass: TriggerRepository::class)]
#[ORM\Table(name: 'point_triggers')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Trigger extends FormEntity implements UuidInterface
{
    use UuidTrait;
    use ProjectTrait;
    #[ORM\ManyToMany(targetEntity: \Mautic\ProjectBundle\Entity\Project::class, cascade: ['merge', 'persist', 'detach'], fetch: 'LAZY', indexBy: 'name')]
    #[ORM\JoinTable(name: 'point_trigger_projects_xref')]
    #[ORM\JoinColumn(name: 'point_trigger_id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'project_id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private \Doctrine\Common\Collections\Collection $projects;

    public const ENTITY_NAME = 'point_trigger';

    /**
     * @var int
     */
    #[Groups(['trigger:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var string
     */
    #[Groups(['trigger:read', 'trigger:write'])]
    #[Assert\NotBlank(message: 'mautic.core.name.required')]
    #[ORM\Column(type: 'string', length: 191)]
    private $name;

    /**
     * @var string|null
     */
    #[Groups(['trigger:read', 'trigger:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private $description;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['trigger:read', 'trigger:write'])]
    #[ORM\Column(name: 'publish_up', type: 'datetime', nullable: true)]
    private $publishUp;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['trigger:read', 'trigger:write'])]
    #[ORM\Column(name: 'publish_down', type: 'datetime', nullable: true)]
    private $publishDown;

    /**
     * @var int
     */
    #[Groups(['trigger:read', 'trigger:write'])]
    #[ORM\Column(type: 'integer')]
    private $points = 0;

    /**
     * @var string
     */
    #[Groups(['trigger:read', 'trigger:write'])]
    #[ORM\Column(type: 'string', length: 7)]
    private $color = 'a0acb8';

    /**
     * @var bool
     */
    #[Groups(['trigger:read', 'trigger:write'])]
    #[ORM\Column(name: 'trigger_existing_leads', type: 'boolean')]
    private $triggerExistingLeads = false;

    /**
     * @var Category|null
     */
    #[Groups(['trigger:read', 'trigger:write'])]
    #[ORM\ManyToOne(targetEntity: \Mautic\CategoryBundle\Entity\Category::class, cascade: ['merge', 'detach'])]
    #[ORM\JoinColumn(name: 'category_id', onDelete: 'SET NULL')]
    private $category;

    /**
     * @var ArrayCollection<int, TriggerEvent>
     */
    #[Groups(['trigger:read', 'trigger:write'])]
    #[ORM\OneToMany(mappedBy: 'trigger', targetEntity: TriggerEvent::class, cascade: ['all'], fetch: 'EXTRA_LAZY', indexBy: 'id')]
    #[ORM\OrderBy(['order' => 'ASC'])]
    private $events;

    #[Groups(['trigger:read', 'trigger:write'])]
    #[ORM\ManyToOne(targetEntity: Group::class)]
    #[ORM\JoinColumn(name: 'group_id', onDelete: 'CASCADE')]
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
    protected function isChanged($prop, $val): void
    {
        if ('events' == $prop) {
            // changes are already computed so just add them
            $this->changes[$prop][$val[0] ?? ''] = $val[1];
        } else {
            parent::isChanged($prop, $val);
        }
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $description
     */
    public function setDescription($description): static
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $name
     */
    public function setName($name): static
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add events.
     */
    public function addTriggerEvent($key, TriggerEvent $event): static
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
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @param \DateTime $publishUp
     */
    public function setPublishUp($publishUp): static
    {
        $this->isChanged('publishUp', $publishUp);
        $this->publishUp = $publishUp;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getPublishUp()
    {
        return $this->publishUp;
    }

    /**
     * @param \DateTime $publishDown
     */
    public function setPublishDown($publishDown): static
    {
        $this->isChanged('publishDown', $publishDown);
        $this->publishDown = $publishDown;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getPublishDown()
    {
        return $this->publishDown;
    }

    /**
     * @return int
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
     * @return string
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
     * @return bool
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
     * @return Category|null
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
