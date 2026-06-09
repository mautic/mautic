<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata as ORMClassMetadata;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Validator\Constraints\UniqueCompanySegmentAlias;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

#[ApiResource(
    shortName: 'CompanySegments',
    operations: [
        new GetCollection(uriTemplate: '/companysegments', security: "is_granted('lead:lists:viewown')"),
        new Post(uriTemplate: '/companysegments', security: "is_granted('lead:lists:create')"),
        new Get(uriTemplate: '/companysegments/{id}', security: "is_granted('lead:lists:viewown', object)"),
        new Put(uriTemplate: '/companysegments/{id}', security: "is_granted('lead:lists:editown', object)"),
        new Patch(uriTemplate: '/companysegments/{id}', security: "is_granted('lead:lists:editother', object)"),
        new Delete(uriTemplate: '/companysegments/{id}', security: "is_granted('lead:lists:deleteown', object)"),
    ],
    normalizationContext: [
        'groups'                  => ['companysegment:read'],
        'swagger_definition_name' => 'Read',
        'api_included'            => ['category'],
    ],
    denormalizationContext: [
        'groups'                  => ['companysegment:write'],
        'swagger_definition_name' => 'Write',
    ]
)]
class CompanySegment extends FormEntity
{
    public const TABLE_NAME    = 'company_segments';
    public const LINKED_ENTITY = 'company';
    public const DEFAULT_ALIAS = 'cs';

    private ?int $id = null;

    private ?string $name = null;

    private ?string $publicName = null;

    private ?Category $category = null;

    private ?string $description = null;

    private ?string $alias = null;

    /**
     * @var array<array<mixed>>
     */
    private array $filters = [];

    /**
     * @var Collection<int, SegmentCompany>
     */
    private Collection $segmentCompanies;

    private ?\DateTimeInterface $lastBuiltDate = null;

    private ?float $lastBuiltTime = null;

    public function __construct()
    {
        $this->segmentCompanies = new ArrayCollection();
    }

    public static function loadMetadata(ORMClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(CompanySegmentRepository::class)
            ->addLifecycleEvent('initializeLastBuiltDate', 'prePersist')
            ->addIndex(['alias'], 'company_segment_alias');

        $builder->addIdColumns();

        $builder->addField('alias', Types::STRING);

        $builder->createField('publicName', Types::STRING)
            ->columnName('public_name')
            ->build();

        $builder->addCategory();

        $builder->addField('filters', Types::JSON);

        $builder->createOneToMany('segmentCompanies', SegmentCompany::class)
            ->mappedBy('companySegment')
            ->fetchExtraLazy()
            ->build();

        $builder->createField('lastBuiltDate', Types::DATETIME_MUTABLE)
            ->columnName('last_built_date')
            ->nullable()
            ->build();

        $builder->createField('lastBuiltTime', Types::FLOAT)
            ->columnName('last_built_time')
            ->nullable()
            ->build();
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('name', new NotBlank(
            ['message' => 'mautic.core.name.required']
        ));

        $metadata->addConstraint(new UniqueCompanySegmentAlias([
            'field'   => 'alias',
            'message' => 'mautic.lead.list.alias.unique',
        ]));
    }

    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('companySegment')
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
                ]
            )
            ->build();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(?string $name): self
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        if (null === $this->alias || '' === $this->alias) {
            $this->setAlias($name);
        }

        if (null === $this->publicName || '' === $this->publicName) {
            $this->setPublicName($name);
        }

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setDescription(?string $description): self
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setCategory(?Category $category): self
    {
        $this->isChanged('category', $category);
        $this->category = $category;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function getPublicName(): ?string
    {
        return $this->publicName;
    }

    public function setPublicName(?string $publicName): self
    {
        if (null === $publicName || '' === $publicName) {
            $publicName = $this->name;
        }

        $this->isChanged('publicName', $publicName);
        $this->publicName = $publicName;

        return $this;
    }

    /**
     * @param array<array<mixed>> $filters
     */
    public function setFilters(array $filters): self
    {
        $this->isChanged('filters', $filters);
        $this->filters = $filters;

        return $this;
    }

    /**
     * @return array<array<mixed>>
     */
    public function getFilters(): array
    {
        $filters = $this->filters;
        foreach ($filters as &$filter) {
            \assert(is_array($filter));
            $filter['glue'] = 'and';
            break;
        }

        return $filters;
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

    public function setAlias(?string $alias): self
    {
        if (null === $alias || '' === $alias) {
            $alias = $this->name;
        }

        $this->isChanged('alias', $alias);
        $this->alias = $alias;

        return $this;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * @return Collection<int, SegmentCompany>
     */
    public function getSegmentCompanies(): Collection
    {
        return $this->segmentCompanies;
    }

    public function addSegmentCompany(SegmentCompany $segmentCompany): void
    {
        if ($this->segmentCompanies->contains($segmentCompany)) {
            return;
        }

        if ($this->segmentCompanies->exists(static fn (int $key, SegmentCompany $existingSegmentCompany): bool => $existingSegmentCompany->getCompanySegment() === $segmentCompany->getCompanySegment()
            && $existingSegmentCompany->getCompany() === $segmentCompany->getCompany())) {
            return;
        }

        $this->segmentCompanies->add($segmentCompany);
    }

    public function removeSegmentCompany(SegmentCompany $segmentCompany): void
    {
        if (!$this->segmentCompanies->contains($segmentCompany)) {
            return;
        }

        $this->segmentCompanies->removeElement($segmentCompany);
    }

    public function hasCompany(Company $company): bool
    {
        return $this->segmentCompanies->exists(
            static fn (int $key, SegmentCompany $segmentCompany): bool => $segmentCompany->getCompany() === $company
                && !$segmentCompany->isManuallyRemoved()
        );
    }

    public function getLastBuiltDate(): ?\DateTimeInterface
    {
        return $this->lastBuiltDate;
    }

    public function setLastBuiltDate(?\DateTimeInterface $lastBuiltDate): void
    {
        $this->lastBuiltDate = $lastBuiltDate;
    }

    public function setLastBuiltDateToCurrentDatetime(): void
    {
        $now = (new DateTimeHelper())->getUtcDateTime();
        $this->setLastBuiltDate($now);
    }

    public function initializeLastBuiltDate(): void
    {
        if ($this->getLastBuiltDate() instanceof \DateTimeInterface) {
            return;
        }

        $this->setLastBuiltDateToCurrentDatetime();
    }

    public function getLastBuiltTime(): ?float
    {
        return $this->lastBuiltTime;
    }

    public function setLastBuiltTime(?float $lastBuiltTime): void
    {
        $this->lastBuiltTime = $lastBuiltTime;
    }

    public function __clone()
    {
        parent::__clone();

        $this->id               = null;
        $this->segmentCompanies = new ArrayCollection();
        $this->setIsPublished(false);
        $this->setAlias('');
        $this->lastBuiltDate = null;
    }
}
