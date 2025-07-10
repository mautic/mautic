<?php

namespace Mautic\CategoryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * @ApiResource(
 *   collectionOperations={
 *     "get",
 *     "post"={"security"="'category:categories:create'"}
 *   },
 *   itemOperations={
 *     "get"={"security"="'category:categories:view'"},
 *     "put"={"security"="'category:categories:edit'"},
 *     "patch"={"security"="'category:categories:edit'"},
 *     "delete"={"security"="'category:categories:delete'"},
 *   },
 *   attributes={
 *     "normalization_context"={
 *       "groups"={
 *         "category:read"
 *        },
 *       "swagger_definition_name"="Read"
 *     },
 *     "denormalization_context"={
 *       "groups"={
 *         "category:write"
 *       },
 *       "swagger_definition_name"="Write"
 *     }
 *   }
 * )
 */
class Category extends FormEntity implements UuidInterface
{
    use UuidTrait;

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var string
     */
    private $alias;

    /**
     * @var string|null
     */
    private $color;

    /**
     * @var string
     */
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

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint(
            'title',
            new NotBlank(
                [
                    'message' => 'mautic.core.title.required',
                ]
            )
        );

        $metadata->addPropertyConstraint(
            'bundle',
            new NotBlank(
                [
                    'message' => 'mautic.core.value.required',
                ]
            )
        );
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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return Category
     */
    public function setTitle($title)
    {
        $this->isChanged('title', $title);
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set alias.
     *
     * @param string $alias
     *
     * @return Category
     */
    public function setAlias($alias)
    {
        $this->isChanged('alias', $alias);
        $this->alias = $alias;

        return $this;
    }

    /**
     * Get alias.
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return Category
     */
    public function setDescription($description)
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
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
     * Get color.
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set bundle.
     *
     * @param string $bundle
     */
    public function setBundle($bundle): void
    {
        $this->isChanged('bundle', $bundle);
        $this->bundle = $bundle;
    }

    /**
     * Get bundle.
     *
     * @return string
     */
    public function getBundle()
    {
        return $this->bundle;
    }
}
