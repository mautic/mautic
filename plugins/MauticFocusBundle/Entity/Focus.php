<?php

namespace MauticPlugin\MauticFocusBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Mautic\FormBundle\Entity\Form;
use Mautic\ProjectBundle\Entity\ProjectTrait;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/focus_items', security: "is_granted('focus:items:viewown')"),
        new Get(uriTemplate: '/focus_items/{id}', security: "is_granted('focus:items:viewown', object)"),
        new Post(uriTemplate: '/focus_items', security: "is_granted('focus:items:create')"),
        new Put(uriTemplate: '/focus_items/{id}', security: "is_granted('focus:items:editown', object)"),
        new Patch(uriTemplate: '/focus_items/{id}', security: "is_granted('focus:items:editother', object)"),
        new Delete(uriTemplate: '/focus_items/{id}', security: "is_granted('focus:items:deleteown', object)"),
    ],
    normalizationContext: [
        'groups'                  => ['focus:read'],
        'swagger_definition_name' => 'Read',
    ],
    denormalizationContext: [
        'groups'                  => ['focus:write'],
        'swagger_definition_name' => 'Write',
    ]
)]
class Focus extends FormEntity implements UuidInterface
{
    use UuidTrait;
    use ProjectTrait;

    /**
     * @var int
     */
    #[Groups(['focus:read'])]
    private $id;

    /**
     * @var string|null
     */
    #[Groups(['focus:read', 'focus:write'])]
    private $description;

    /**
     * @var string|null
     */
    #[Groups(['focus:read', 'focus:write'])]
    private $editor;

    /**
     * @var string|null
     */
    #[Groups(['focus:read', 'focus:write'])]
    private $html;

    /**
     * @var string|null
     */
    #[Groups(['focus:read', 'focus:write'])]
    private $htmlMode;

    /**
     * @var string
     */
    #[Groups(['focus:read', 'focus:write'])]
    private $name;

    #[Groups(['focus:read', 'focus:write'])]
    private $category;

    /**
     * @var string
     */
    #[Groups(['focus:read', 'focus:write'])]
    private $type;

    /**
     * @var string|null
     */
    #[Groups(['focus:read', 'focus:write'])]
    private $website;

    /**
     * @var string
     */
    #[Groups(['focus:read', 'focus:write'])]
    private $style;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['focus:read', 'focus:write'])]
    private $publishUp;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['focus:read', 'focus:write'])]
    private $publishDown;

    /**
     * @var array<mixed>
     */
    #[Groups(['focus:read', 'focus:write'])]
    private $properties = [];

    /**
     * @var array
     */
    #[Groups(['focus:read', 'focus:write'])]
    private $utmTags = [];

    /**
     * @var int|null
     */
    private $form;

    /**
     * @var string|null
     */
    private $cache;

    public function __construct()
    {
        $this->initializeProjects();
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint(
            'name',
            new NotBlank(
                [
                    'message' => 'mautic.core.name.required',
                ]
            )
        );

        $metadata->addPropertyConstraint(
            'type',
            new NotBlank(
                ['message' => 'mautic.focus.error.select_type']
            )
        );

        $metadata->addPropertyConstraint(
            'style',
            new NotBlank(
                ['message' => 'mautic.focus.error.select_style']
            )
        );
    }

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('focus')
            ->setCustomRepositoryClass(FocusRepository::class)
            ->addIndex(['focus_type'], 'focus_type')
            ->addIndex(['style'], 'focus_style')
            ->addIndex(['form_id'], 'focus_form')
            ->addIndex(['name'], 'focus_name');

        $builder->addIdColumns();

        $builder->addCategory();

        $builder->addNamedField('type', 'string', 'focus_type');

        $builder->addField('style', 'string');

        $builder->addNullableField('website', 'string');

        $builder->addPublishDates();

        $builder->addNullableField('properties', 'array');

        $builder->createField('utmTags', 'array')
            ->columnName('utm_tags')
            ->nullable()
            ->build();

        $builder->addNamedField('form', 'integer', 'form_id', true);

        $builder->addNullableField('cache', 'text');

        $builder->createField('htmlMode', 'string')
            ->columnName('html_mode')
            ->nullable()
            ->build();

        $builder->addNullableField('editor', 'text');

        $builder->addNullableField('html', 'text');

        static::addUuidField($builder);
        self::addProjectsField($builder, 'focus_projects_xref', 'focus_id');
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('focus')
            ->addListProperties(
                [
                    'id',
                    'name',
                    'category',
                ]
            )
            ->addProperties(
                [
                    'description',
                    'type',
                    'website',
                    'style',
                    'publishUp',
                    'publishDown',
                    'properties',
                    'utmTags',
                    'form',
                    'htmlMode',
                    'html',
                    'editor',
                    'cache',
                ]
            )
            ->build();

        self::addProjectsInLoadApiMetadata($metadata, 'focus');
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description): static
    {
        $this->isChanged('description', $description);

        $this->description = $description;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEditor()
    {
        return $this->editor;
    }

    public function setEditor($editor): static
    {
        $this->isChanged('editor', $editor);

        $this->editor = $editor;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getHtml()
    {
        return $this->html;
    }

    public function setHtml($html): static
    {
        $this->isChanged('html', $html);

        $this->html = $html;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getHtmlMode()
    {
        return $this->htmlMode;
    }

    public function setHtmlMode($htmlMode): static
    {
        $this->isChanged('htmlMode', $htmlMode);

        $this->htmlMode = $htmlMode;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): static
    {
        $this->isChanged('name', $name);

        $this->name = $name;

        return $this;
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
    public function setCategory($category): static
    {
        $this->isChanged('category', $category);

        $this->category = $category;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPublishUp()
    {
        return $this->publishUp;
    }

    /**
     * @param mixed $publishUp
     */
    public function setPublishUp($publishUp): static
    {
        $this->isChanged('publishUp', $publishUp);

        $this->publishUp = $publishUp;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPublishDown()
    {
        return $this->publishDown;
    }

    /**
     * @param mixed $publishDown
     */
    public function setPublishDown($publishDown): static
    {
        $this->isChanged('publishDown', $publishDown);

        $this->publishDown = $publishDown;

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param array<mixed> $properties
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
    public function getUtmTags()
    {
        return $this->utmTags;
    }

    /**
     * @param array $utmTags
     */
    public function setUtmTags($utmTags): static
    {
        $this->isChanged('utmTags', $utmTags);
        $this->utmTags = $utmTags;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): static
    {
        $this->isChanged('type', $type);

        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * @param mixed $style
     */
    public function setStyle($style): static
    {
        $this->isChanged('style', $style);

        $this->style = $style;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param mixed $website
     */
    public function setWebsite($website): static
    {
        $this->isChanged('website', $website);

        $this->website = $website;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param mixed $form
     */
    public function setForm($form): static
    {
        if ($form instanceof Form) {
            $form = $form->getId();
        }

        $this->isChanged('form', $form);

        $this->form = $form;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param mixed $cache
     */
    public function setCache($cache): static
    {
        $this->cache = $cache;

        return $this;
    }
}
