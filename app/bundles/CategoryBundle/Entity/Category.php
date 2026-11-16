<?php

declare(strict_types=1);

namespace Mautic\CategoryBundle\Entity;

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
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints\NotBlank;

#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(security: "is_granted('category:categories:create')"),
        new Get(security: "is_granted('category:categories:view')"),
        new Put(security: "is_granted('category:categories:edit')"),
        new Patch(security: "is_granted('category:categories:edit')"),
        new Delete(security: "is_granted('category:categories:delete')"),
    ],
    normalizationContext: [
        'groups'                  => ['category:read'],
        'swagger_definition_name' => 'Read',
    ],
    denormalizationContext: [
        'groups'                  => ['category:write'],
        'swagger_definition_name' => 'Write',
    ]
)]
class Category extends FormEntity implements UuidInterface
{
    use UuidTrait;

    /**
     * @var int
     */
    #[Groups(['category:read', 'stage:read', 'asset:read', 'download:read', 'event:read', 'leadcategory:read', 'notification:read', 'dynamicContent:read', 'webhook:read', 'sms:read', 'page:read', 'campaign:read', 'email:read', 'point:read', 'trigger:read', 'message:read', 'focus:read', 'form:read', 'beeFreeRow:read', 'segment:read'])]
    private $id;

    /**
     * @var string
     */
    #[Groups(['category:read', 'category:write', 'stage:read', 'asset:read', 'download:read', 'event:read', 'leadcategory:read', 'notification:read', 'dynamicContent:read', 'webhook:read', 'sms:read', 'page:read', 'campaign:read', 'email:read', 'point:read', 'trigger:read', 'message:read', 'focus:read', 'form:read', 'beeFreeRow:read', 'segment:read'])]
    #[NotBlank(message: 'mautic.core.title.required')]
    private $title;

    /**
     * @var string|null
     */
    #[Groups(['category:read', 'category:write', 'stage:read', 'asset:read', 'download:read', 'event:read', 'leadcategory:read', 'notification:read', 'dynamicContent:read', 'webhook:read', 'sms:read', 'page:read', 'campaign:read', 'email:read', 'point:read', 'trigger:read', 'message:read', 'focus:read', 'form:read', 'beeFreeRow:read', 'segment:read'])]
    private $description;

    /**
     * @var string
     */
    #[Groups(['category:read', 'category:write', 'stage:read', 'asset:read', 'download:read', 'event:read', 'leadcategory:read', 'notification:read', 'dynamicContent:read', 'webhook:read', 'sms:read', 'page:read', 'campaign:read', 'email:read', 'point:read', 'trigger:read', 'message:read', 'focus:read', 'form:read', 'beeFreeRow:read', 'segment:read'])]
    private $alias;

    /**
     * @var string|null
     */
    #[Groups(['category:read', 'category:write', 'stage:read', 'asset:read', 'download:read', 'event:read', 'leadcategory:read', 'notification:read', 'dynamicContent:read', 'webhook:read', 'sms:read', 'page:read', 'campaign:read', 'email:read', 'point:read', 'trigger:read', 'message:read', 'focus:read', 'form:read', 'beeFreeRow:read', 'segment:read'])]
    private $color;

    /**
     * @var string
     */
    #[Groups(['category:read', 'category:write', 'stage:read', 'asset:read', 'download:read', 'event:read', 'leadcategory:read', 'notification:read', 'dynamicContent:read', 'webhook:read', 'sms:read', 'page:read', 'campaign:read', 'email:read', 'point:read', 'trigger:read', 'message:read', 'focus:read', 'form:read', 'beeFreeRow:read', 'segment:read'])]
    #[NotBlank(message: 'mautic.core.value.required')]
    private $bundle;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('categories')
            ->setCustomRepositoryClass(CategoryRepository::class)
            ->addIndex(['alias'], 'category_alias_search');

        $builder->addIdColumns('title');

        $builder->addField('alias', 'string');

        $builder->createField('color', 'string')
            ->nullable()
            ->length(7)
            ->build();

        $builder->createField('bundle', 'string')
            ->length(50)
            ->build();

        static::addUuidField($builder);
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('category')
            ->addListProperties(
                [
                    'id',
                    'title',
                    'alias',
                    'description',
                    'color',
                    'bundle',
                ]
            )
            ->build();
    }

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    /**
     * @return int|null
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
     * @return string|null
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
     * @return string|null
     */
    public function getAlias()
    {
        return $this->alias;
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
     * @param string $color
     */
    public function setColor($color): void
    {
        $this->isChanged('color', $color);
        $this->color = $color;
    }

    /**
     * @return string|null
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param string $bundle
     */
    public function setBundle($bundle): void
    {
        $this->isChanged('bundle', $bundle);
        $this->bundle = $bundle;
    }

    /**
     * @return string|null
     */
    public function getBundle()
    {
        return $this->bundle;
    }
}
