<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\DBAL\Types\Types;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('tagManager:tagManager:view')"),
        new Post(security: "is_granted('tagManager:tagManager:create')"),
        new Get(security: "is_granted('tagManager:tagManager:view')"),
        new Put(security: "is_granted('tagManager:tagManager:edit')"),
        new Patch(security: "is_granted('tagManager:tagManager:edit')"),
        new Delete(security: "is_granted('tagManager:tagManager:delete')"),
    ],
    normalizationContext: [
        'groups'                  => ['leadfield:read'],
        'swagger_definition_name' => 'Read',
    ],
    denormalizationContext: [
        'groups'                  => ['leadfield:write'],
        'swagger_definition_name' => 'Write',
    ]
)]
#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\Table(name: 'lead_tags')]
#[ORM\Index(columns: ['tag'], name: 'lead_tag_search')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Tag implements UuidInterface
{
    use UuidTrait;

    /**
     * @var int
     */
    #[Groups(['leadfield:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    #[Groups(['leadfield:read', 'leadfield:write'])]
    #[ORM\Column(type: Types::STRING, length: 191)]
    private ?string $tag;

    /**
     * @var string|null
     */
    #[Groups(['leadfield:read', 'leadfield:write'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private $description;

    public ?int $deletedId = null;

    public function __construct(?string $tag = null, bool $clean = true)
    {
        $this->tag = $clean && $tag ? $this->validateTag($tag) : $tag;
    }

    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('tag')
            ->addListProperties(
                [
                    'id',
                    'tag',
                    'description',
                ]
            )
            ->build();
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function setTag(string $tag): static
    {
        $this->tag = $this->validateTag($tag);

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
     * @param string $description
     */
    public function setDescription($description): static
    {
        $this->description = $description;

        return $this;
    }

    private function validateTag(string $tag): string
    {
        return InputHelper::string(trim($tag));
    }
}
