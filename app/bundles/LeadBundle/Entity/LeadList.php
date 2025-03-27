<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Form\Validator\Constraints\SegmentInUse;
use Mautic\LeadBundle\Form\Validator\Constraints\UniqueUserAlias;
use Mautic\LeadBundle\Validator\Constraints\SegmentUsedInCampaigns;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * @ApiResource(
 *   attributes={
 *     "security"="false",
 *     "normalization_context"={
 *       "groups"={
 *         "segment:read"
 *        },
 *       "swagger_definition_name"="Read"
 *     },
 *     "denormalization_context"={
 *       "groups"={
 *         "segment:write"
 *       },
 *       "swagger_definition_name"="Write"
 *     }
 *   }
 * )
 */
class LeadList extends FormEntity implements UuidInterface
{
    use UuidTrait;

    public const TABLE_NAME = 'lead_lists';

    /**
     * @var int|null
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $publicName;

    /**
     * @var Category|null
     **/
    private $category;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var string
     */
    private $alias;

    /**
     * @var array
     */
    private $filters = [];

    /**
     * @var bool
     */
    private $isGlobal = true;

    /**
     * @var bool
     */
    private $isPreferenceCenter = false;

    /**
     * @var ArrayCollection<ListLead>
     */
    private $leads;

    /**
     * @var \DateTimeInterface|null
     */
    private $lastBuiltDate;

    /**
     * @var float|null
     */
    private $lastBuiltTime;

    public function __construct()
    {
        $this->leads = new ArrayCollection();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(LeadListRepository::class)
            ->addIndex(['alias'], 'lead_list_alias');

        $builder->addIdColumns();

        $builder->addField('alias', 'string');

        $builder->createField('publicName', 'string')
            ->columnName('public_name')
            ->build();

        $builder->addCategory();

        $builder->addField('filters', 'array');

        $builder->createField('isGlobal', 'boolean')
            ->columnName('is_global')
            ->build();

        $builder->createField('isPreferenceCenter', 'boolean')
            ->columnName('is_preference_center')
            ->build();

        $builder->createOneToMany('leads', 'ListLead')
            ->setIndexBy('id')
            ->mappedBy('list')
            ->fetchExtraLazy()
            ->build();

        $builder->createField('lastBuiltDate', 'datetime')
            ->columnName('last_built_date')
            ->nullable()
            ->build();

        $builder->createField('lastBuiltTime', 'float')
            ->columnName('last_built_time')
            ->nullable()
            ->build();

        static::addUuidField($builder);
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank(
            ['message' => 'mautic.core.name.required']
        ));

        $metadata->addConstraint(new UniqueUserAlias([
            'field'   => 'alias',
            'message' => 'mautic.lead.list.alias.unique',
        ]));

        $metadata->addConstraint(new SegmentUsedInCampaigns());
        $metadata->addConstraint(new SegmentInUse());
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
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string|null $name
     *
     * @return LeadList
     */
    public function setName($name)
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
     *
     * @return LeadList
     */
    public function setDescription($description)
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

    public function setCategory(Category $category = null): LeadList
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
     * Get publicName.
     *
     * @return string|null
     */
    public function getPublicName()
    {
        return $this->publicName;
    }

    /**
     * @param string|null $publicName
     *
     * @return LeadList
     */
    public function setPublicName($publicName)
    {
        $this->isChanged('publicName', $publicName);
        $this->publicName = $publicName;

        return $this;
    }

    /**
     * @return LeadList
     */
    public function setFilters(array $filters)
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
        if (null === $this->getLastBuiltDate()) {
            return true;
        }
        if (null !== $this->getDateModified() && $this->getDateModified()->getTimestamp() >= $this->getLastBuiltDate()->getTimestamp()) {
            return true;
        }

        return false;
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
     *
     * @return LeadList
     */
    public function setIsGlobal($isGlobal)
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
        return $this->getIsGlobal();
    }

    /**
     * @param string|null $alias
     *
     * @return LeadList
     */
    public function setAlias($alias)
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
     */
    private function addLegacyParams(array $filters): array
    {
        return array_map(
            function (array $filter): array {
                if (isset($filter['properties']) && $filter['properties'] && array_key_exists('filter', $filter['properties'])) {
                    $filter['filter'] = $filter['properties']['filter'];
                } else {
                    $filter['filter'] = $filter['filter'] ?? null;
                }

                if (isset($filter['properties']) && $filter['properties'] && array_key_exists('display', $filter['properties'])) {
                    $filter['display'] = $filter['properties']['display'];
                } else {
                    $filter['display'] = $filter['display'] ?? null;
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
        $now = (new DateTimeHelper())->getUtcDateTime();
        $this->setLastBuiltDate($now);
    }

    /**
     * @deprecated Initialisation is no longer necessary and lastBuiltDate is allowed to be null
     */
    public function initializeLastBuiltDate(): void
    {
    }

    public function getLastBuiltTime(): ?float
    {
        return $this->lastBuiltTime;
    }

    public function setLastBuiltTime(?float $lastBuiltTime): void
    {
        $this->lastBuiltTime = $lastBuiltTime;
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
}
