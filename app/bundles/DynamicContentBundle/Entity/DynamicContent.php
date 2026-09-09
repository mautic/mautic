<?php

namespace Mautic\DynamicContentBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FiltersEntityTrait;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\TranslationEntityInterface;
use Mautic\CoreBundle\Entity\TranslationEntityTrait;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Mautic\CoreBundle\Entity\VariantEntityInterface;
use Mautic\CoreBundle\Entity\VariantEntityTrait;
use Mautic\DynamicContentBundle\DynamicContent\TypeList;
use Mautic\DynamicContentBundle\Validator\Constraints\NoNesting;
use Mautic\DynamicContentBundle\Validator\Constraints\SlotNameType;
use Mautic\ProjectBundle\Entity\ProjectTrait;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('dynamiccontent:dynamiccontents:viewown')"),
        new Post(security: "is_granted('dynamiccontent:dynamiccontents:create')"),
        new Get(security: "is_granted('dynamiccontent:dynamiccontents:viewown', object)"),
        new Put(security: "is_granted('dynamiccontent:dynamiccontents:editown', object)"),
        new Patch(security: "is_granted('dynamiccontent:dynamiccontents:editother', object)"),
        new Delete(security: "is_granted('dynamiccontent:dynamiccontents:deleteown', object)"),
    ],
    normalizationContext: [
        'groups'                  => ['dynamicContent:read'],
        'swagger_definition_name' => 'Read',
        'api_included'            => ['category', 'translationChildren'],
    ],
    denormalizationContext: [
        'groups'                  => ['dynamicContent:write'],
        'swagger_definition_name' => 'Write',
    ]
)]
#[ORM\Entity(repositoryClass: DynamicContentRepository::class)]
#[ORM\Table(name: 'dynamic_content')]
#[ORM\Index(columns: ['is_campaign_based'], name: 'is_campaign_based_index')]
#[ORM\Index(columns: ['slot_name'], name: 'slot_name_index')]
#[ORM\HasLifecycleCallbacks]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
/**
 * @use TranslationEntityTrait<DynamicContent>
 * @use VariantEntityTrait<DynamicContent>
 */
class DynamicContent extends FormEntity implements VariantEntityInterface, TranslationEntityInterface, UuidInterface
{
    use TranslationEntityTrait;
    use VariantEntityTrait;
    use FiltersEntityTrait;
    use UuidTrait;
    use ProjectTrait;
    #[ORM\ManyToMany(targetEntity: \Mautic\ProjectBundle\Entity\Project::class, cascade: ['merge', 'persist', 'detach'], fetch: 'LAZY', indexBy: 'name')]
    #[ORM\JoinTable(name: 'dynamic_content_projects_xref')]
    #[ORM\JoinColumn(name: 'dynamic_content_id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'project_id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private \Doctrine\Common\Collections\Collection $projects;

    public const ENTITY_NAME = 'dynamic_content';

    /**
     * @var int
     */
    #[Groups(['dynamicContent:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    #[Groups(['dynamicContent:read', 'dynamicContent:write'])]
    #[ORM\Column(type: 'string', length: 191)]
    private ?string $name = null;

    #[Groups(['dynamicContent:read', 'dynamicContent:write'])]
    private string $type = TypeList::HTML;

    #[Groups(['dynamicContent:read', 'dynamicContent:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[Groups(['dynamicContent:read', 'dynamicContent:write'])]
    #[ORM\ManyToOne(targetEntity: \Mautic\CategoryBundle\Entity\Category::class, cascade: ['merge', 'detach'])]
    #[ORM\JoinColumn(name: 'category_id', onDelete: 'SET NULL')]
    private ?Category $category = null;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['dynamicContent:read', 'dynamicContent:write'])]
    #[ORM\Column(name: 'publish_up', type: 'datetime', nullable: true)]
    private $publishUp;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['dynamicContent:read', 'dynamicContent:write'])]
    #[ORM\Column(name: 'publish_down', type: 'datetime', nullable: true)]
    private $publishDown;

    /**
     * @var string|null
     */
    #[Groups(['dynamicContent:read', 'dynamicContent:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private $content;

    /**
     * @var array|null
     */
    #[Groups(['dynamicContent:read', 'dynamicContent:write'])]
    #[ORM\Column(name: 'utm_tags', type: Types::JSON, nullable: true)]
    private $utmTags = [];

    /**
     * @var int
     */
    #[Groups(['dynamicContent:read'])]
    #[ORM\Column(name: 'sent_count', type: 'integer')]
    private $sentCount = 0;

    /**
     * @var ArrayCollection<Stat>
     */
    #[Groups(['dynamicContent:read'])]
    #[ORM\OneToMany(mappedBy: 'dynamicContent', targetEntity: 'Stat', cascade: ['persist'], fetch: 'EXTRA_LAZY', indexBy: 'id')]
    private $stats;

    /**
     * @var bool
     */
    #[Groups(['dynamicContent:read', 'dynamicContent:write'])]
    #[ORM\Column(name: 'is_campaign_based', type: 'boolean', options: ['default' => 1])]
    private $isCampaignBased = true;

    /**
     * @var string|null
     */
    #[Groups(['dynamicContent:read', 'dynamicContent:write'])]
    #[ORM\Column(name: 'slot_name', type: 'string', length: 191, nullable: true)]
    private $slotName;

    public function __construct()
    {
        $this->stats               = new ArrayCollection();
        $this->translationChildren = new ArrayCollection();
        $this->variantChildren     = new ArrayCollection();
        $this->initializeProjects();
    }

    public function __clone()
    {
        $this->id                  = null;
        $this->sentCount           = 0;
        $this->stats               = new ArrayCollection();
        $this->translationChildren = new ArrayCollection();
        $this->variantChildren     = new ArrayCollection();

        parent::__clone();
    }

    public function clearStats(): void
    {
        $this->stats = new ArrayCollection();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->addField(
            'type',
            Types::STRING,
            [
                'length'  => 10,
                'default' => TypeList::HTML,
            ]
        );

        self::addTranslationMetadata($builder, self::class);
        self::addVariantMetadata($builder, self::class);
        self::addFiltersMetadata($builder);
    }

    /**
     * @throws \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     * @throws \Symfony\Component\Validator\Exception\InvalidOptionsException
     * @throws \Symfony\Component\Validator\Exception\MissingOptionsException
     */
    public static function loadValidatorMetaData(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('name', new NotBlank(message: 'mautic.core.name.required'));
        $metadata->addPropertyConstraint('content', new NoNesting());

        $metadata->addPropertyConstraint('type', new NotBlank(message: 'mautic.core.type.required'));
        $metadata->addPropertyConstraint('type', new Choice(choices: new TypeList()->getChoices()));

        $metadata->addConstraint(new SlotNameType());

        $metadata->addConstraint(new Callback(
            function (self $dwc, ExecutionContextInterface $context): void {
                if (!$dwc->getIsCampaignBased()) {
                    $validator  = $context->getValidator();
                    $violations = $validator->validate(
                        $dwc->getSlotName(),
                        [
                            new NotBlank(
                                message: 'mautic.dynamicContent.slot_name.notblank'
                            ),
                        ]
                    );
                    foreach ($violations as $violation) {
                        $context->buildViolation($violation->getMessage())
                                ->atPath('slotName')
                                ->addViolation();
                    }
                    $violations = $validator->validate(
                        $dwc->getFilters(),
                        [
                            new Count(min: 1, minMessage: 'mautic.dynamicContent.filter.options.empty'),
                        ]
                    );
                    foreach ($violations as $violation) {
                        $context->buildViolation($violation->getMessage())
                                ->atPath('filters')
                                ->addViolation();
                    }
                }
            },
        ));
    }

    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('dwc')
            ->addListProperties([
                'id',
                'name',
                'category',
                'type',
            ])
            ->addProperties([
                'publishUp',
                'publishDown',
                'sentCount',
                'variantParent',
                'variantChildren',
                'content',
                'utmTags',
                'filters',
                'isCampaignBased',
                'slotName',
            ])
            ->setMaxDepth(1, 'variantParent')
            ->setMaxDepth(1, 'variantChildren')
            ->build();

        self::addProjectsInLoadApiMetadata($metadata, 'dwc');
    }

    protected function isChanged($prop, $val): void
    {
        $getter  = 'get'.ucfirst($prop);
        $current = $this->{$getter}();

        if ('variantParent' == $prop || 'translationParent' == $prop || 'category' == $prop) {
            $currentId = ($current) ? $current->getId() : '';
            $newId     = ($val) ? $val->getId() : null;
            if ($currentId != $newId) {
                $this->changes[$prop] = [$currentId, $newId];
            }
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function setType(string $type): void
    {
        $type = strtolower($type);
        $this->isChanged('type', $type);
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->isChanged('category', $category);
        $this->category = $category;

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
    public function getPublishDown()
    {
        return $this->publishDown;
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
     * @return string|null
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent($content): static
    {
        $this->isChanged('content', $content);
        $this->content = $content;

        return $this;
    }

    /**
     * @return int
     */
    public function getSentCount(bool $includeVariants = false)
    {
        return $includeVariants ? $this->getAccumulativeTranslationCount('getSentCount') : $this->sentCount;
    }

    public function setSentCount($sentCount): static
    {
        $this->sentCount = $sentCount;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getStats()
    {
        return $this->stats;
    }

    /**
     * @return bool
     */
    public function getIsCampaignBased()
    {
        return $this->isCampaignBased;
    }

    /**
     * @param bool $isCampaignBased
     */
    public function setIsCampaignBased($isCampaignBased): static
    {
        $this->isChanged('isCampaignBased', $isCampaignBased);
        $this->isCampaignBased = $isCampaignBased;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSlotName()
    {
        return $this->slotName;
    }

    /**
     * @param string $slotName
     */
    public function setSlotName($slotName): static
    {
        $this->isChanged('slotName', $slotName);
        $this->slotName = $slotName;

        return $this;
    }

    /**
     * Lifecycle callback to clear the slot name if is_campaign is true.
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function cleanSlotName(): void
    {
        if ($this->isCampaignBased) {
            $this->setSlotName('');
        }
    }

    public function setUtmTags(array $utmTags): static
    {
        $this->isChanged('utmTags', $utmTags);
        $this->utmTags = $utmTags;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getUtmTags()
    {
        return $this->utmTags;
    }
}
