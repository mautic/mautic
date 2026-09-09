<?php

namespace Mautic\LeadBundle\Entity;

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
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Form\Validator\Constraints\SegmentInUse;
use Mautic\LeadBundle\Form\Validator\Constraints\UniqueUserAlias;
use Mautic\LeadBundle\Validator\Constraints\SegmentUsedInCampaigns;
use Mautic\ProjectBundle\Entity\ProjectTrait;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Segments',
    operations: [
        new GetCollection(uriTemplate: '/segments', security: "is_granted('lead:lists:viewown')"),
        new Post(uriTemplate: '/segments', security: "is_granted('lead:lists:create')"),
        new Get(uriTemplate: '/segments/{id}', security: "is_granted('lead:lists:viewown', object)"),
        new Put(uriTemplate: '/segments/{id}', security: "is_granted('lead:lists:editown', object)"),
        new Patch(uriTemplate: '/segments/{id}', security: "is_granted('lead:lists:editother', object)"),
        new Delete(uriTemplate: '/segments/{id}', security: "is_granted('lead:lists:deleteown', object)"),
    ],
    normalizationContext: [
        'groups'                  => ['segment:read'],
        'swagger_definition_name' => 'Read',
        'api_included'            => ['category'],
    ],
    denormalizationContext: [
        'groups'                  => ['segment:write'],
        'swagger_definition_name' => 'Write',
    ]
)]
#[UniqueUserAlias(field: 'alias', message: 'mautic.lead.list.alias.unique')]
#[SegmentUsedInCampaigns]
#[SegmentInUse]
#[ORM\Entity(repositoryClass: LeadListRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\Index(columns: ['alias'], name: 'lead_list_alias')]
#[ORM\Index(columns: ['deleted'], name: 'segment_deleted')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class LeadList extends FormEntity implements UuidInterface
{
    use UuidTrait;

    use ProjectTrait;
    #[ORM\ManyToMany(targetEntity: \Mautic\ProjectBundle\Entity\Project::class, cascade: ['merge', 'persist', 'detach'], fetch: 'LAZY', indexBy: 'name')]
    #[ORM\JoinTable(name: 'lead_list_projects_xref')]
    #[ORM\JoinColumn(name: 'leadlist_id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'project_id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private \Doctrine\Common\Collections\Collection $projects;

    public const TABLE_NAME  = 'lead_lists';

    public const ENTITY_NAME = 'lists';

    /**
     * @var int|null
     */
    #[Groups(['segment:read', 'campaign:read', 'email:read', 'sms:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var string
     */
    #[Groups(['segment:read', 'segment:write', 'campaign:read', 'email:read', 'sms:read'])]
    #[Assert\NotBlank(message: 'mautic.core.name.required')]
    #[ORM\Column(type: 'string', length: 191)]
    private $name;

    /**
     * @var string
     */
    #[Groups(['segment:read', 'segment:write', 'campaign:read', 'email:read', 'sms:read'])]
    #[ORM\Column(name: 'public_name', type: 'string', length: 191)]
    private $publicName;

    /**
     * @var Category|null
     */
    #[Groups(['segment:read', 'segment:write', 'campaign:read', 'email:read', 'sms:read'])]
    #[ORM\ManyToOne(targetEntity: \Mautic\CategoryBundle\Entity\Category::class, cascade: ['merge', 'detach'])]
    #[ORM\JoinColumn(name: 'category_id', onDelete: 'SET NULL')]
    private $category;

    /**
     * @var string|null
     */
    #[Groups(['segment:read', 'segment:write', 'campaign:read', 'email:read', 'sms:read'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private $description;

    /**
     * @var string
     */
    #[Groups(['segment:read', 'segment:write', 'campaign:read', 'email:read', 'sms:read'])]
    #[ORM\Column(type: 'string', length: 191)]
    private $alias;

    /**
     * @var array
     */
    #[Groups(['segment:read', 'segment:write', 'campaign:read', 'email:read', 'sms:read'])]
    #[ORM\Column(type: 'array')]
    private $filters = [];

    /**
     * @var bool
     */
    #[Groups(['segment:read', 'segment:write', 'campaign:read', 'email:read', 'sms:read'])]
    #[ORM\Column(name: 'is_global', type: 'boolean')]
    private $isGlobal = true;

    /**
     * @var bool
     */
    #[Groups(['segment:read', 'segment:write', 'campaign:read', 'email:read', 'sms:read'])]
    #[ORM\Column(name: 'is_preference_center', type: 'boolean')]
    private $isPreferenceCenter = false;

    /**
     * @var ArrayCollection<ListLead>
     */
    #[ORM\OneToMany(mappedBy: 'list', targetEntity: ListLead::class, fetch: 'EXTRA_LAZY')]
    private $leads;

    #[Groups(['segment:read', 'campaign:read', 'email:read', 'sms:read'])]
    #[ORM\Column(name: 'last_built_date', type: 'datetime', nullable: true)]
    private \DateTime|\DateTimeInterface|null $lastBuiltDate = null;

    #[Groups(['segment:read', 'campaign:read', 'email:read', 'sms:read'])]
    #[ORM\Column(name: 'last_built_time', type: 'float', nullable: true)]
    private ?float $lastBuiltTime = null;

    #[Groups(['segment:read', 'campaign:read', 'email:read', 'sms:read'])]
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $deleted = null;

    public function __construct()
    {
        $this->leads = new ArrayCollection();
        $this->initializeProjects();
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('leadList')
            ->addListProperties(
                [
                    'id',
                    'name',
                    'publicName',
                    'alias',
                    'description',
                    'category',
                ]
            )
            ->addProperties(
                [
                    'filters',
                    'isGlobal',
                    'isPreferenceCenter',
                ]
            )
            ->build();

        self::addProjectsInLoadApiMetadata($metadata, 'leadList');
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * @param string|null $name
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
     * @param string|null $description
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

    public function setCategory(?Category $category = null): self
    {
        $this->isChanged('category', $category);
        $this->category = $category;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * @return string|null
     */
    public function getPublicName()
    {
        return $this->publicName;
    }

    /**
     * @param string|null $publicName
     */
    public function setPublicName($publicName): static
    {
        $this->isChanged('publicName', $publicName);
        $this->publicName = $publicName;

        return $this;
    }

    public function setFilters(array $filters): static
    {
        $this->isChanged('filters', $filters);
        $this->filters = $filters;

        return $this;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        if (is_array($this->filters)) {
            return $this->setFirstFilterGlueToAnd($this->addLegacyParams($this->filters)); // @phpstan-ignore method.deprecated
        }

        return $this->filters;
    }

    public function needsRebuild(): bool
    {
        // Manual or unpublished segments never require rebuild
        if (empty($this->getFilters()) || !$this->isPublished()) {
            return false;
        }

        // A segment with filters requires rebuild if it was changed since the last build date, or was never built
        if (null === $this->lastBuiltDate) {
            return true;
        }

        return null !== $this->getDateModified() && $this->getDateModified()->getTimestamp() >= $this->lastBuiltDate->getTimestamp();
    }

    public function hasFilterTypeOf(string $type): bool
    {
        foreach ($this->getFilters() as $filter) {
            if ($filter['type'] === $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param bool $isGlobal
     */
    public function setIsGlobal($isGlobal): static
    {
        $this->isChanged('isGlobal', (bool) $isGlobal);
        $this->isGlobal = (bool) $isGlobal;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsGlobal()
    {
        return $this->isGlobal;
    }

    /**
     * Proxy function to getIsGlobal().
     *
     * @return bool
     */
    public function isGlobal()
    {
        return $this->isGlobal;
    }

    /**
     * @param string|null $alias
     */
    public function setAlias($alias): static
    {
        $this->isChanged('alias', $alias);
        $this->alias = $alias;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLeads()
    {
        return $this->leads;
    }

    /**
     * Clone entity with empty contact list.
     */
    public function __clone()
    {
        parent::__clone();

        $this->id    = null;
        $this->leads = new ArrayCollection();
        $this->setIsPublished(false);
        $this->setAlias('');
        $this->lastBuiltDate = null;
    }

    /**
     * @return bool
     */
    public function getIsPreferenceCenter()
    {
        return $this->isPreferenceCenter;
    }

    /**
     * @param bool $isPreferenceCenter
     */
    public function setIsPreferenceCenter($isPreferenceCenter): void
    {
        $this->isChanged('isPreferenceCenter', (bool) $isPreferenceCenter);
        $this->isPreferenceCenter = (bool) $isPreferenceCenter;
    }

    /**
     * @deprecated remove after several of years.
     *
     * This is needed go keep BC after we moved 'filter' and 'display' params
     * to the 'properties' array.
     *
     * @param mixed[][] $filters
     */
    private function addLegacyParams(array $filters): array
    {
        return array_map(
            function (array $filter): array {
                if (isset($filter['properties']) && $filter['properties'] && array_key_exists('filter', $filter['properties'])) {
                    $filter['filter'] = $filter['properties']['filter'];
                } else {
                    $filter['filter'] ??= null;
                }

                if (isset($filter['properties']) && $filter['properties'] && array_key_exists('display', $filter['properties'])) {
                    $filter['display'] = $filter['properties']['display'];
                } else {
                    $filter['display'] ??= null;
                }

                return $filter;
            },
            $filters
        );
    }

    public function getLastBuiltDate(): ?\DateTimeInterface
    {
        return $this->lastBuiltDate;
    }

    public function setLastBuiltDate(?\DateTime $lastBuiltDate): void
    {
        $this->lastBuiltDate = $lastBuiltDate;
    }

    public function setLastBuiltDateToCurrentDatetime(): void
    {
        $now = new DateTimeHelper()->getUtcDateTime();
        $this->setLastBuiltDate($now);
    }

    public function getLastBuiltTime(): ?float
    {
        return $this->lastBuiltTime;
    }

    public function setLastBuiltTime(?float $lastBuiltTime): void
    {
        $this->lastBuiltTime = $lastBuiltTime;
    }

    public function setDeleted(?\DateTimeInterface $deletedDate): void
    {
        $this->deleted = $deletedDate;
    }

    public function getDeleted(): ?\DateTimeInterface
    {
        return $this->deleted;
    }

    /**
     * @param mixed[] $filters
     *
     * @return mixed[]
     */
    private function setFirstFilterGlueToAnd(array $filters): array
    {
        foreach ($filters as &$filter) {
            $filter['glue'] = 'and';
            break;
        }

        return $filters;
    }

    public function isDeleted(): bool
    {
        return null !== $this->deleted;
    }
}
