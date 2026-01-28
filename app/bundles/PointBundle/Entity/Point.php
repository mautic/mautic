<?php

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
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Mautic\CoreBundle\Helper\IntHelper;
use Mautic\ProjectBundle\Entity\ProjectTrait;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('point:triggers:viewown')"),
        new Post(security: "is_granted('point:triggers:create')"),
        new Get(security: "is_granted('point:triggers:viewown')"),
        new Put(security: "is_granted('point:triggers:editown')"),
        new Patch(security: "is_granted('point:triggers:editother')"),
        new Delete(security: "is_granted('point:triggers:deleteown')"),
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
class Point extends FormEntity implements UuidInterface
{
    use UuidTrait;
    use ProjectTrait;
    public const ENTITY_NAME = 'point';

    /**
     * @var int
     */
    #[Groups(['point:read'])]
    private $id;

    /**
     * @var string
     */
    #[Groups(['point:read', 'point:write'])]
    private $name;

    /**
     * @var string|null
     */
    #[Groups(['point:read', 'point:write'])]
    private $description;

    /**
     * @var string
     */
    #[Groups(['point:read', 'point:write'])]
    private $type;

    /**
     * @var bool
     */
    #[Groups(['point:read', 'point:write'])]
    private $repeatable = false;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['point:read', 'point:write'])]
    private $publishUp;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['point:read', 'point:write'])]
    private $publishDown;

    /**
     * @var int
     */
    #[Groups(['point:read', 'point:write'])]
    private $delta = 0;

    /**
     * @var array
     */
    #[Groups(['point:read', 'point:write'])]
    private $properties = [];

    /**
     * @var ArrayCollection<int,LeadPointLog>
     */
    private $log;

    /**
     * @var Category|null
     **/
    #[Groups(['point:read', 'point:write'])]
    private $category;

    #[Groups(['point:read', 'point:write'])]
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

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('points')
            ->setCustomRepositoryClass(PointRepository::class)
            ->addIndex(['type'], 'point_type_search');

        $builder->addIdColumns();

        $builder->createField('type', 'string')
            ->length(50)
            ->build();

        $builder->addPublishDates();

        $builder->createField('repeatable', 'boolean')
            ->build();

        $builder->addField('delta', 'integer');

        $builder->addField('properties', 'array');

        $builder->createOneToMany('log', 'LeadPointLog')
            ->mappedBy('point')
            ->cascadePersist()
            ->cascadeRemove()
            ->fetchExtraLazy()
            ->build();

        $builder->addCategory();

        $builder->createManyToOne('group', Group::class)
            ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
            ->build();

        static::addUuidField($builder);
        self::addProjectsField($builder, 'point_projects_xref', 'point_id');
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank([
            'message' => 'mautic.core.name.required',
        ]));

        $metadata->addPropertyConstraint('type', new Assert\NotBlank([
            'message' => 'mautic.point.type.notblank',
        ]));

        $metadata->addPropertyConstraint('delta', new Assert\NotBlank([
            'message' => 'mautic.point.delta.notblank',
        ]));

        $metadata->addPropertyConstraint('delta', new Assert\Range([
            'min' => IntHelper::MIN_INTEGER_VALUE,
            'max' => IntHelper::MAX_INTEGER_VALUE,
        ]));
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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param array $properties
     *
     * @return self
     */
    public function setProperties($properties)
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
     *
     * @return self
     */
    public function setType($type)
    {
        $this->isChanged('type', $type);
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
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
     *
     * @return self
     */
    public function setDescription($description)
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $name
     *
     * @return self
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return self
     */
    public function addLog(LeadPointLog $log)
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
     *
     * @return Point
     */
    public function setPublishUp($publishUp)
    {
        $this->isChanged('publishUp', $publishUp);
        $this->publishUp = $publishUp;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getPublishUp()
    {
        return $this->publishUp;
    }

    /**
     * @param \DateTime $publishDown
     *
     * @return Point
     */
    public function setPublishDown($publishDown)
    {
        $this->isChanged('publishDown', $publishDown);
        $this->publishDown = $publishDown;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getPublishDown()
    {
        return $this->publishDown;
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
    public function setCategory($category): void
    {
        $this->category = $category;
    }

    /**
     * @return mixed
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
     *
     * @return Point
     */
    public function setRepeatable($repeatable)
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
