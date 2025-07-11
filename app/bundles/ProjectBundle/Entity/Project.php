<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata as OrmClassMetadata;
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
 *   attributes={
 *     "security"="false",
 *     "normalization_context"={
 *       "groups"={
 *         "project:read"
 *        },
 *       "swagger_definition_name"="Read"
 *     },
 *     "denormalization_context"={
 *       "groups"={
 *         "project:write"
 *       },
 *       "swagger_definition_name"="Write"
 *     }
 *   }
 * )
 */
class Project extends FormEntity implements UuidInterface
{
    use UuidTrait;

    public const TABLE_NAME = 'projects';

    /**
     * @Groups("project:read")
     */
    private ?int $id = null;

    /**
     * @Groups({"project:read", "project:write"})
     */
    private ?string $description = null;

    /**
     * @Groups({"project:read", "project:write"})
     */
    private ?string $name = null;

    /**
     * @var mixed[]
     *
     * @Groups({"project:read", "project:write"})
     */
    private array $properties = [];

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    public static function loadMetadata(OrmClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(ProjectRepository::class)
            ->addIndex(['name'], 'project_name');

        $builder->addIdColumns();

        $builder->addField('properties', Types::JSON);

        static::addUuidField($builder);
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

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint(
            'name',
            new NotBlank(['message' => 'mautic.core.name.required'])
        );
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
