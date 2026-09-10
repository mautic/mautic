<?php

declare(strict_types=1);

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
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Mautic\FormBundle\Entity\Form;
use Mautic\ProjectBundle\Entity\ProjectTrait;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints\NotBlank;

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
#[ORM\Entity(repositoryClass: FocusRepository::class)]
#[ORM\Table(name: 'focus')]
#[ORM\Index(columns: ['focus_type'], name: 'focus_type')]
#[ORM\Index(columns: ['style'], name: 'focus_style')]
#[ORM\Index(columns: ['form_id'], name: 'focus_form')]
#[ORM\Index(columns: ['name'], name: 'focus_name')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Focus extends FormEntity implements UuidInterface
{
    use UuidTrait;
    use ProjectTrait;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \Mautic\ProjectBundle\Entity\Project>
     */
    #[ORM\ManyToMany(targetEntity: \Mautic\ProjectBundle\Entity\Project::class, cascade: ['merge', 'persist', 'detach'], fetch: 'LAZY', indexBy: 'name')]
    #[ORM\JoinTable(name: 'focus_projects_xref')]
    #[ORM\JoinColumn(name: 'focus_id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'project_id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private \Doctrine\Common\Collections\Collection $projects;

    /**
     * @var int
     */
    #[Groups(['focus:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var string|null
     */
    #[Groups(['focus:read', 'focus:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private $description;

    /**
     * @var string|null
     */
    #[Groups(['focus:read', 'focus:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private $editor;

    /**
     * @var string|null
     */
    #[Groups(['focus:read', 'focus:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private $html;

    /**
     * @var string|null
     */
    #[Groups(['focus:read', 'focus:write'])]
    #[ORM\Column(name: 'html_mode', type: 'string', length: 191, nullable: true)]
    private $htmlMode;

    /**
     * @var string
     */
    #[Groups(['focus:read', 'focus:write'])]
    #[NotBlank(message: 'mautic.core.name.required')]
    #[ORM\Column(type: 'string', length: 191)]
    private $name;

    #[Groups(['focus:read', 'focus:write'])]
    #[ORM\ManyToOne(targetEntity: \Mautic\CategoryBundle\Entity\Category::class, cascade: ['merge', 'detach'])]
    #[ORM\JoinColumn(name: 'category_id', onDelete: 'SET NULL')]
    private $category;

    /**
     * @var string
     */
    #[Groups(['focus:read', 'focus:write'])]
    #[NotBlank(message: 'mautic.focus.error.select_type')]
    #[ORM\Column(name: 'focus_type', type: 'string', length: 191)]
    private $type;

    /**
     * @var string|null
     */
    #[Groups(['focus:read', 'focus:write'])]
    #[ORM\Column(type: 'string', length: 191, nullable: true)]
    private $website;

    /**
     * @var string
     */
    #[Groups(['focus:read', 'focus:write'])]
    #[NotBlank(message: 'mautic.focus.error.select_style')]
    #[ORM\Column(type: 'string', length: 191)]
    private $style;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['focus:read', 'focus:write'])]
    #[ORM\Column(name: 'publish_up', type: 'datetime', nullable: true)]
    private $publishUp;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['focus:read', 'focus:write'])]
    #[ORM\Column(name: 'publish_down', type: 'datetime', nullable: true)]
    private $publishDown;

    /**
     * @var array<mixed>
     */
    #[Groups(['focus:read', 'focus:write'])]
    #[ORM\Column(type: 'array', nullable: true)]
    private $properties = [];

    /**
     * @var array
     */
    #[Groups(['focus:read', 'focus:write'])]
    #[ORM\Column(name: 'utm_tags', type: 'array', nullable: true)]
    private $utmTags = [];

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'form_id', type: 'integer', nullable: true)]
    private $form;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private $cache;

    public function __construct()
    {
        $this->initializeProjects();
    }

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
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
