<?php

namespace Mautic\StageBundle\Entity;

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
use Mautic\ProjectBundle\Entity\ProjectTrait;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('stage:stages:viewown')"),
        new Post(security: "is_granted('stage:stages:create')"),
        new Get(security: "is_granted('stage:stages:viewown')"),
        new Put(security: "is_granted('stage:stages:editown')"),
        new Patch(security: "is_granted('stage:stages:editother')"),
        new Delete(security: "is_granted('stage:stages:deleteown')"),
    ],
    normalizationContext: [
        'groups'                  => ['stage:read'],
        'swagger_definition_name' => 'Read',
        'api_included'            => ['category'],
    ],
    denormalizationContext: [
        'groups'                  => ['stage:write'],
        'swagger_definition_name' => 'Write',
    ]
)]
class Stage extends FormEntity implements UuidInterface
{
    use UuidTrait;
    use ProjectTrait;

    /**
     * @var int
     */
    #[Groups(['stage:read'])]
    private $id;

    /**
     * @var string
     */
    #[Groups(['stage:read', 'stage:write'])]
    private $name;

    /**
     * @var string|null
     */
    #[Groups(['stage:read', 'stage:write'])]
    private $description;

    /**
     * @var int
     */
    #[Groups(['stage:read', 'stage:write'])]
    private $weight = 0;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['stage:read', 'stage:write'])]
    private $publishUp;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['stage:read', 'stage:write'])]
    private $publishDown;

    /**
     * @var ArrayCollection<int,LeadStageLog>
     */
    private $log;

    /**
     * @var Category|null
     **/
    #[Groups(['stage:read', 'stage:write'])]
    private $category;

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
        $builder->setTable('stages')
            ->setCustomRepositoryClass(StageRepository::class);

        $builder->addIdColumns();

        $builder->createField('weight', 'integer')
            ->build();

        $builder->addPublishDates();

        $builder->createOneToMany('log', 'LeadStageLog')
            ->mappedBy('stage')
            ->cascadePersist()
            ->cascadeRemove()
            ->fetchExtraLazy()
            ->build();

        $builder->addCategory();

        static::addUuidField($builder);
        self::addProjectsField($builder, 'stage_projects_xref', 'stage_id');
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank([
            'message' => 'mautic.core.name.required',
        ]));
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('stage')
            ->addListProperties(
                [
                    'id',
                    'name',
                    'category',
                    'weight',
                    'description',
                ]
            )
            ->addProperties(
                [
                    'publishUp',
                    'publishDown',
                ]
            )
            ->build();

        self::addProjectsInLoadApiMetadata($metadata, 'stage');
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setWeight($type): self
    {
        $this->weight = (int) $type;

        return $this;
    }

    /**
     * @return int
     */
    public function getWeight()
    {
        return $this->weight;
    }

    public function convertToArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * @param string $description
     */
    public function setDescription($description): self
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
     */
    public function setName($name): self
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

    public function addLog(LeadStageLog $log): self
    {
        $this->log[] = $log;

        return $this;
    }

    public function removeLog(LeadStageLog $log): void
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
     */
    public function setPublishUp($publishUp): self
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
     */
    public function setPublishDown($publishDown): Stage
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
}
