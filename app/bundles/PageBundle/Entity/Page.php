<?php

namespace Mautic\PageBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\OptimisticLockInterface;
use Mautic\CoreBundle\Entity\OptimisticLockTrait;
use Mautic\CoreBundle\Entity\TranslationEntityInterface;
use Mautic\CoreBundle\Entity\TranslationEntityTrait;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Mautic\CoreBundle\Entity\VariantEntityInterface;
use Mautic\CoreBundle\Entity\VariantEntityTrait;
use Mautic\CoreBundle\Validator\EntityEvent;
use Mautic\ProjectBundle\Entity\ProjectTrait;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('page:pages:viewown')"),
        new Post(security: "is_granted('page:pages:create')"),
        new Get(security: "is_granted('page:pages:viewown', object)"),
        new Put(security: "is_granted('page:pages:editown', object)"),
        new Patch(security: "is_granted('page:pages:editother', object)"),
        new Delete(security: "is_granted('page:pages:deleteown', object)"),
    ],
    normalizationContext: [
        'groups'                  => ['page:read'],
        'swagger_definition_name' => 'Read',
        'api_included'            => ['category', 'translationChildren'],
    ],
    denormalizationContext: [
        'groups'                  => ['page:write'],
        'swagger_definition_name' => 'Write',
    ]
)]
/**
 * @use TranslationEntityTrait<Page>
 * @use VariantEntityTrait<Page>
 */
#[EntityEvent]
class Page extends FormEntity implements TranslationEntityInterface, VariantEntityInterface, UuidInterface, OptimisticLockInterface
{
    use TranslationEntityTrait;
    use VariantEntityTrait;
    use UuidTrait;
    use ProjectTrait;
    use OptimisticLockTrait;

    public const ENTITY_NAME = 'page';

    public const TABLE_NAME = 'pages';

    /**
     * @var int
     */
    #[Groups(['page:read', 'download:read', 'email:read'])]
    private $id;

    /**
     * @var string
     */
    #[Groups(['page:read', 'page:write', 'download:read', 'email:read'])]
    #[NotBlank(message: 'mautic.core.title.required')]
    private $title;

    /**
     * @var string
     */
    #[Groups(['page:read', 'page:write', 'download:read', 'email:read'])]
    private $alias;

    /**
     * @var string|null
     */
    #[Groups(['page:read', 'page:write', 'download:read', 'email:read'])]
    private $template;

    /**
     * @var string|null
     */
    #[Groups(['page:read', 'page:write', 'download:read', 'email:read'])]
    private $customHtml;

    /**
     * @var array
     */
    #[Groups(['page:read', 'page:write', 'download:read', 'email:read'])]
    private $content = [];

    /**
     * @var \DateTimeInterface|null
     */
    #[Groups(['page:read', 'page:write', 'download:read', 'email:read'])]
    private $publishUp;

    /**
     * @var \DateTimeInterface|null
     */
    #[Groups(['page:read', 'page:write', 'download:read', 'email:read'])]
    private $publishDown;

    /**
     * @var int
     */
    #[Groups(['page:read', 'page:write', 'download:read', 'email:read'])]
    private $hits = 0;

    /**
     * @var int
     */
    #[Groups(['page:read', 'page:write', 'download:read', 'email:read'])]
    private $uniqueHits = 0;

    /**
     * @var int
     */
    #[Groups(['page:read', 'page:write', 'download:read', 'email:read'])]
    private $variantHits = 0;

    /**
     * @var int
     */
    #[Groups(['page:read', 'page:write', 'download:read', 'email:read'])]
    private $revision = 1;

    /**
     * @var string|null
     */
    #[Groups(['page:read', 'page:write', 'download:read', 'email:read'])]
    private $metaDescription;

    /**
     * @var string|null
     */
    #[Groups(['page:read', 'page:write', 'download:read', 'email:read'])]
    private $headScript;

    /**
     * @var string|null
     */
    #[Groups(['page:read', 'page:write', 'download:read', 'email:read'])]
    private $footerScript;

    /**
     * @var string|null
     */
    #[Groups(['page:read', 'page:write', 'download:read', 'email:read'])]
    private $redirectType;

    /**
     * @var string|null
     */
    #[Groups(['page:read', 'page:write', 'download:read', 'email:read'])]
    private $redirectUrl;

    /**
     * @var Category|null
     */
    #[Groups(['page:read', 'page:write', 'download:read', 'email:read'])]
    private $category;

    /**
     * @var bool|null
     */
    #[Groups(['page:read', 'page:write', 'download:read', 'email:read'])]
    private $isPreferenceCenter;

    /**
     * @var bool|null
     */
    #[Groups(['page:read', 'page:write', 'download:read', 'email:read'])]
    private $noIndex;

    /**
     * Used to identify the page for the builder.
     */
    private $sessionId;

    private ?PageDraft $draft = null;

    private bool $isCloned = false;

    private ?int $cloneObjectId = null;

    #[Groups(['page:read', 'page:write', 'download:read', 'email:read'])]
    private ?bool $publicPreview = true;

    #[Groups(['page:read', 'page:write', 'download:read', 'email:read'])]
    private bool $isDuplicate = false;

    public function __clone()
    {
        $this->cloneObjectId = (int) $this->id;
        $this->isCloned      = true;
        $this->id            = null;
        $this->sessionId     = 'new_'.hash('sha1', uniqid((string) mt_rand()));
        $this->clearTranslations();
        $this->clearVariants();
        $this->setDraft(null);

        parent::__clone();
    }

    public function __construct()
    {
        $this->translationChildren = new ArrayCollection();
        $this->variantChildren     = new ArrayCollection();
        $this->initializeProjects();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(PageRepository::class)
            ->addIndex(['alias'], 'page_alias_search');

        $builder->addId();

        $builder->addField('title', 'string');

        $builder->addField('alias', 'string');

        $builder->addNullableField('template', 'string');

        $builder->createField('customHtml', 'text')
            ->columnName('custom_html')
            ->nullable()
            ->build();

        $builder->createField('content', 'array')
            ->nullable()
            ->build();

        $builder->addPublishDates();

        $builder->addField('hits', 'integer');

        $builder->createField('uniqueHits', 'integer')
            ->columnName('unique_hits')
            ->build();

        $builder->createField('variantHits', 'integer')
            ->columnName('variant_hits')
            ->build();

        $builder->addField('revision', 'integer');

        $builder->createField('metaDescription', 'string')
            ->columnName('meta_description')
            ->nullable()
            ->build();

        $builder->createField('headScript', 'text')
            ->columnName('head_script')
            ->nullable()
            ->build();

        $builder->createField('footerScript', 'text')
            ->columnName('footer_script')
            ->nullable()
            ->build();

        $builder->createField('redirectType', 'string')
            ->columnName('redirect_type')
            ->nullable()
            ->length(100)
            ->build();

        $builder->createField('redirectUrl', 'string')
            ->columnName('redirect_url')
            ->nullable()
            ->length(2048)
            ->build();

        $builder->addCategory();

        $builder->createField('isPreferenceCenter', 'boolean')
            ->columnName('is_preference_center')
            ->nullable()
            ->build();

        $builder->createField('noIndex', 'boolean')
            ->columnName('no_index')
            ->nullable()
            ->build();

        $builder->createOneToOne('draft', PageDraft::class)
            ->mappedBy('page')
            ->fetchExtraLazy()
            ->cascadeAll()
            ->build();

        $builder->addNullableField('publicPreview', Types::BOOLEAN, 'public_preview');

        self::addTranslationMetadata($builder, self::class);
        self::addVariantMetadata($builder, self::class);
        static::addUuidField($builder);
        self::addProjectsField($builder, 'page_projects_xref', 'page_id');
        self::addVersionField($builder);
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addConstraint(new Callback(
            function (Page $page, ExecutionContextInterface $context): void {
                $type = $page->getRedirectType();
                if (null !== $type) {
                    $validator  = $context->getValidator();
                    $violations = $validator->validate(
                        $page->getRedirectUrl(),
                        [
                            new Assert\Url(),
                            new NotBlank(message: 'mautic.core.value.required'),
                        ],
                    );

                    foreach ($violations as $violation) {
                        $context->buildViolation($violation->getMessage())
                            ->atPath('redirectUrl')
                            ->addViolation();
                    }
                }

                if ($page->isVariant()) {
                    // Get a summation of weights
                    $parent   = $page->getVariantParent();
                    $children = $parent instanceof \Mautic\CoreBundle\Entity\VariantEntityInterface ? $parent->getVariantChildren() : $page->getVariantChildren();

                    $total = 0;
                    foreach ($children as $child) {
                        $settings = $child->getVariantSettings();
                        $total += (int) $settings['weight'];
                    }

                    if ($total > 100) {
                        $context->buildViolation('mautic.core.variant_weights_invalid')
                            ->atPath('variantSettings[weight]')
                            ->addViolation();
                    }
                }
            },
        ));
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('page')
            ->addListProperties(
                [
                    'id',
                    'title',
                    'alias',
                    'category',
                ]
            )
            ->addProperties(
                [
                    'language',
                    'publishUp',
                    'publishDown',
                    'hits',
                    'uniqueHits',
                    'variantHits',
                    'revision',
                    'metaDescription',
                    'redirectType',
                    'redirectUrl',
                    'isPreferenceCenter',
                    'noIndex',
                    'variantSettings',
                    'variantStartDate',
                    'variantParent',
                    'variantChildren',
                    'translationParent',
                    'translationChildren',
                    'template',
                    'customHtml',
                ]
            )
            ->setMaxDepth(1, 'variantParent')
            ->setMaxDepth(1, 'variantChildren')
            ->setMaxDepth(1, 'translationParent')
            ->setMaxDepth(1, 'translationChildren')
            ->build();

        self::addProjectsInLoadApiMetadata($metadata, 'page');
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $title
     */
    public function setTitle($title): static
    {
        $this->isChanged('title', $title);
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $alias
     */
    public function setAlias($alias): static
    {
        $this->isChanged('alias', $alias);
        $this->alias = $alias;

        return $this;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param array<string> $content
     */
    public function setContent($content): static
    {
        $this->isChanged('content', $content);
        $this->content = $content;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getContent()
    {
        return $this->content;
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
     * @param int $hits
     */
    public function setHits($hits): static
    {
        $this->hits = $hits;

        return $this;
    }

    /**
     * @param bool $includeVariants
     *
     * @return int|mixed
     */
    public function getHits($includeVariants = false)
    {
        return ($includeVariants) ? $this->getAccumulativeVariantCount('getHits') : $this->hits;
    }

    /**
     * @param int $revision
     */
    public function setRevision($revision): static
    {
        $this->revision = $revision;

        return $this;
    }

    /**
     * @return int
     */
    public function getRevision()
    {
        return $this->revision;
    }

    /**
     * @param string $metaDescription
     */
    public function setMetaDescription($metaDescription): static
    {
        $this->isChanged('metaDescription', $metaDescription);
        $this->metaDescription = $metaDescription;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMetaDescription()
    {
        return $this->metaDescription;
    }

    /**
     * @param string $headScript
     */
    public function setHeadScript($headScript): static
    {
        $this->headScript = $headScript;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getHeadScript()
    {
        return $this->headScript;
    }

    /**
     * @param string $footerScript
     */
    public function setFooterScript($footerScript): static
    {
        $this->footerScript = $footerScript;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFooterScript()
    {
        return $this->footerScript;
    }

    /**
     * @param ?string $redirectType
     */
    public function setRedirectType($redirectType): static
    {
        $this->isChanged('redirectType', $redirectType);
        $this->redirectType = $redirectType;

        return $this;
    }

    /**
     * @return ?string
     */
    public function getRedirectType()
    {
        return $this->redirectType;
    }

    /**
     * @param string $redirectUrl
     */
    public function setRedirectUrl($redirectUrl): static
    {
        $this->isChanged('redirectUrl', $redirectUrl);
        $this->redirectUrl = $redirectUrl;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    public function setCategory(?Category $category = null): static
    {
        $this->isChanged('category', $category);
        $this->category = $category;

        return $this;
    }

    /**
     * @return Category|null
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param bool|null $isPreferenceCenter
     */
    public function setIsPreferenceCenter($isPreferenceCenter): static
    {
        $sanitizedValue = null === $isPreferenceCenter ? null : (bool) $isPreferenceCenter;
        $this->isChanged('isPreferenceCenter', $sanitizedValue);
        $this->isPreferenceCenter = $sanitizedValue;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsPreferenceCenter()
    {
        return $this->isPreferenceCenter;
    }

    /**
     * @param bool|null $noIndex
     */
    public function setNoIndex($noIndex): void
    {
        $sanitizedValue = null === $noIndex ? null : (bool) $noIndex;
        $this->isChanged('noIndex', $sanitizedValue);
        $this->noIndex = $sanitizedValue;
    }

    /**
     * @return bool|null
     */
    public function getNoIndex()
    {
        return $this->noIndex;
    }

    /**
     * @param string $id
     */
    public function setSessionId($id): static
    {
        $this->sessionId = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @param string $template
     */
    public function setTemplate($template): static
    {
        $this->isChanged('template', $template);
        $this->template = $template;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTemplate()
    {
        return $this->template;
    }

    protected function isChanged($prop, $val): void
    {
        $getter  = 'get'.ucfirst($prop);
        $current = $this->{$getter}();

        if ('translationParent' == $prop || 'variantParent' == $prop || 'category' == $prop) {
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
     * @param int $uniqueHits
     */
    public function setUniqueHits($uniqueHits): static
    {
        $this->uniqueHits = $uniqueHits;

        return $this;
    }

    /**
     * @return int
     */
    public function getUniqueHits($includeVariants = false)
    {
        return ($includeVariants) ? $this->getAccumulativeVariantCount('getUniqueHits') : $this->uniqueHits;
    }

    /**
     * @param bool $includeVariants
     *
     * @return int|mixed
     */
    public function getVariantHits($includeVariants = false)
    {
        return ($includeVariants) ? $this->getAccumulativeVariantCount('getVariantHits') : $this->variantHits;
    }

    /**
     * @param mixed $variantHits
     */
    public function setVariantHits($variantHits): void
    {
        $this->variantHits = $variantHits;
    }

    /**
     * @return string|null
     */
    public function getCustomHtml()
    {
        return $this->customHtml;
    }

    /**
     * @param mixed $customHtml
     */
    public function setCustomHtml($customHtml): void
    {
        $this->customHtml = $customHtml;
    }

    public function hasDraft(): bool
    {
        return null !== $this->draft;
    }

    public function getDraftContent(): ?string
    {
        return $this->hasDraft() ? $this->draft->getHtml() : null;
    }

    public function getDraft(): ?PageDraft
    {
        return $this->draft;
    }

    public function setDraft(?PageDraft $draft): void
    {
        $this->draft = $draft;
    }

    public function getIsClone(): bool
    {
        return $this->isCloned;
    }

    public function getCloneObjectId(): int
    {
        return $this->cloneObjectId;
    }

    public function getPublicPreview(): bool
    {
        return $this->publicPreview;
    }

    public function isPublicPreview(): bool
    {
        return $this->publicPreview;
    }

    public function setPublicPreview(bool $publicPreview): self
    {
        $this->isChanged('publicPreview', $publicPreview);
        $this->publicPreview = $publicPreview;

        return $this;
    }

    public function isDuplicate(): bool
    {
        return $this->isDuplicate;
    }

    public function setIsDuplicate(bool $isDuplicate): void
    {
        $this->isDuplicate = $isDuplicate;
    }
}
