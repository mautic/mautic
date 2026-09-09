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
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('stage:stages:viewown')"),
        new Post(security: "is_granted('stage:stages:create')"),
        new Get(security: "is_granted('stage:stages:viewown', object)"),
        new Put(security: "is_granted('stage:stages:editown', object)"),
        new Patch(security: "is_granted('stage:stages:editother', object)"),
        new Delete(security: "is_granted('stage:stages:deleteown', object)"),
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
#[UniqueEntity(fields: ['weight'], message: 'mautic.stage.weight.unique')]
#[ORM\Entity(repositoryClass: StageRepository::class)]
#[ORM\Table(name: 'stages')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Stage extends FormEntity implements UuidInterface
{
    use UuidTrait;
    use ProjectTrait;
    #[ORM\ManyToMany(targetEntity: \Mautic\ProjectBundle\Entity\Project::class, cascade: ['merge', 'persist', 'detach'], fetch: 'LAZY', indexBy: 'name')]
    #[ORM\JoinTable(name: 'stage_projects_xref')]
    #[ORM\JoinColumn(name: 'stage_id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'project_id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private \Doctrine\Common\Collections\Collection $projects;

    /**
     * @var int
     */
    #[Groups(['stage:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var string
     */
    #[Groups(['stage:read', 'stage:write'])]
    #[Assert\NotBlank(message: 'mautic.core.name.required')]
    #[ORM\Column(type: 'string', length: 191)]
    private $name;

    /**
     * @var string|null
     */
    #[Groups(['stage:read', 'stage:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private $description;

    /**
     * @var int
     */
    #[Groups(['stage:read', 'stage:write'])]
    #[ORM\Column(type: 'integer')]
    private $weight = 0;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['stage:read', 'stage:write'])]
    #[ORM\Column(name: 'publish_up', type: 'datetime', nullable: true)]
    private $publishUp;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['stage:read', 'stage:write'])]
    #[ORM\Column(name: 'publish_down', type: 'datetime', nullable: true)]
    private $publishDown;

    /**
     * @var ArrayCollection<int,LeadStageLog>
     */
    #[ORM\OneToMany(mappedBy: 'stage', targetEntity: 'LeadStageLog', cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY')]
    private $log;

    /**
     * @var Category|null
     */
    #[Groups(['stage:read', 'stage:write'])]
    #[ORM\ManyToOne(targetEntity: \Mautic\CategoryBundle\Entity\Category::class, cascade: ['merge', 'detach'])]
    #[ORM\JoinColumn(name: 'category_id', onDelete: 'SET NULL')]
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
     * @return int|null
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
     * @return string|null
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
     * @return string|null
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
     * @return \DateTimeInterface|null
     */
    public function getPublishUp()
    {
        return $this->publishUp;
    }

    /**
     * @param \DateTime $publishDown
     */
    public function setPublishDown($publishDown): self
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
     * @return Category|null
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
