<?php

declare(strict_types=1);

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
use Mautic\CoreBundle\Helper\IntHelper;
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
        'groups'                  => ['point:read'],
        'swagger_definition_name' => 'Read',
        'api_included'            => ['category'],
    ],
    denormalizationContext: [
        'groups'                  => ['point:write'],
        'swagger_definition_name' => 'Write',
    ]
)]
#[ORM\Entity(repositoryClass: PointRepository::class)]
#[ORM\Table(name: 'points')]
#[ORM\Index(columns: ['type'], name: 'point_type_search')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Point extends FormEntity implements UuidInterface
{
    use UuidTrait;
    use ProjectTrait;
    #[ORM\ManyToMany(targetEntity: \Mautic\ProjectBundle\Entity\Project::class, cascade: ['merge', 'persist', 'detach'], fetch: 'LAZY', indexBy: 'name')]
    #[ORM\JoinTable(name: 'point_projects_xref')]
    #[ORM\JoinColumn(name: 'point_id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'project_id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private \Doctrine\Common\Collections\Collection $projects;

    public const ENTITY_NAME = 'point';

    /**
     * @var int
     */
    #[Groups(['point:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var string
     */
    #[Groups(['point:read', 'point:write'])]
    #[Assert\NotBlank(message: 'mautic.core.name.required')]
    #[ORM\Column(type: 'string', length: 191)]
    private $name;

    /**
     * @var string|null
     */
    #[Groups(['point:read', 'point:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private $description;

    /**
     * @var string
     */
    #[Groups(['point:read', 'point:write'])]
    #[Assert\NotBlank(message: 'mautic.point.type.notblank')]
    #[ORM\Column(type: 'string', length: 50)]
    private $type;

    /**
     * @var bool
     */
    #[Groups(['point:read', 'point:write'])]
    #[ORM\Column(type: 'boolean')]
    private $repeatable = false;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['point:read', 'point:write'])]
    #[ORM\Column(name: 'publish_up', type: 'datetime', nullable: true)]
    private $publishUp;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['point:read', 'point:write'])]
    #[ORM\Column(name: 'publish_down', type: 'datetime', nullable: true)]
    private $publishDown;

    /**
     * @var int
     */
    #[Groups(['point:read', 'point:write'])]
    #[Assert\NotBlank(message: 'mautic.point.delta.notblank')]
    #[Assert\Range(min: IntHelper::MIN_INTEGER_VALUE, max: IntHelper::MAX_INTEGER_VALUE)]
    #[ORM\Column(type: 'integer')]
    private $delta = 0;

    /**
     * @var array
     */
    #[Groups(['point:read', 'point:write'])]
    #[ORM\Column(type: 'array')]
    private $properties = [];

    /**
     * @var ArrayCollection<int,LeadPointLog>
     */
    #[ORM\OneToMany(mappedBy: 'point', targetEntity: 'LeadPointLog', cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY')]
    private $log;

    /**
     * @var Category|null
     */
    #[Groups(['point:read', 'point:write'])]
    #[ORM\ManyToOne(targetEntity: \Mautic\CategoryBundle\Entity\Category::class, cascade: ['merge', 'detach'])]
    #[ORM\JoinColumn(name: 'category_id', onDelete: 'SET NULL')]
    private $category;

    #[Groups(['point:read', 'point:write'])]
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
        $this->log = new ArrayCollection();
        $this->initializeProjects();
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('point')
            ->addListProperties(
                [
                    'id',
                    'name',
                    'category',
                    'type',
                    'description',
                ]
            )
            ->addProperties(
                [
                    'publishUp',
                    'publishDown',
                    'delta',
                    'properties',
                    'repeatable',
                ]
            )
            ->build();

        self::addProjectsInLoadApiMetadata($metadata, 'point');
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param array $properties
     */
    public function setProperties($properties): static
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
     * @param string $type
     */
    public function setType($type): static
    {
        $this->isChanged('type', $type);
        $this->type = $type;

        return $this;
    }

    /**
     * @return string|null
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

    public function addLog(LeadPointLog $log): static
    {
        $this->log[] = $log;

        return $this;
    }

    public function removeLog(LeadPointLog $log): void
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

    /**
     * @return int
     */
    public function getDelta()
    {
        return $this->delta;
    }

    /**
     * @param mixed $delta
     */
    public function setDelta($delta): void
    {
        $this->delta = (int) $delta;
    }

    /**
     * @param bool $repeatable
     */
    public function setRepeatable($repeatable): static
    {
        $this->isChanged('repeatable', $repeatable);
        $this->repeatable = $repeatable;

        return $this;
    }

    /**
     * @return bool
     */
    public function getRepeatable()
    {
        return $this->repeatable;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    public function setGroup(?Group $group): void
    {
        $this->group = $group;
    }
}
