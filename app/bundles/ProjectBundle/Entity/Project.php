<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Mautic\ProjectBundle\Validator\Constraints\UniqueName;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints\NotBlank;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('project:projects:view')"),
        new Post(security: "is_granted('project:projects:create')"),
        new Get(security: "is_granted('project:projects:view')"),
        new Put(security: "is_granted('project:projects:edit')"),
        new Patch(security: "is_granted('project:projects:edit')"),
        new Delete(security: "is_granted('project:projects:delete')"),
    ],
    normalizationContext: [
        'groups'                  => ['project:read'],
        'swagger_definition_name' => 'Read',
    ],
    denormalizationContext: [
        'groups'                  => ['project:write'],
        'swagger_definition_name' => 'Write',
    ]
)]
#[UniqueName]
#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\UniqueConstraint(name: 'unique_project_name', columns: ['name'])]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Project extends FormEntity implements UuidInterface
{
    use UuidTrait;

    public const TABLE_NAME = 'projects';

    #[Groups(['project:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[Groups(['project:read', 'project:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[Groups(['project:read', 'project:write'])]
    #[NotBlank(message: 'mautic.core.name.required')]
    #[ORM\Column(type: 'string', length: 191)]
    private ?string $name = null;

    /**
     * @var mixed[]
     */
    #[Groups(['project:read', 'project:write'])]
    #[ORM\Column(type: Types::JSON)]
    private array $properties = [];

    /**
     * Transient property to store the count of entities associated with this project.
     * This is not persisted to the database.
     */
    public int $entitiesCount = 0;

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('project')
            ->addListProperties(
                [
                    'id',
                    'name',
                ]
            )
            ->addProperties(
                [
                    'description',
                    'properties',
                ]
            )
            ->build();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->isChanged('description', $description);

        $this->description = $description;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->isChanged('name', $name);

        $this->name = $name;
    }

    /**
     * @return mixed[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param mixed[] $properties
     */
    public function setProperties(array $properties): void
    {
        $this->isChanged('properties', $properties);

        $this->properties = $properties;
    }
}
