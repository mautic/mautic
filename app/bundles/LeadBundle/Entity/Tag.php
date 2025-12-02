<?php

namespace Mautic\LeadBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
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
class Tag implements UuidInterface
{
    use UuidTrait;

    /**
     * @var int
     */
    #[Groups(['leadfield:read'])]
    private $id;

    /**
     * @var string
     */
    #[Groups(['leadfield:read', 'leadfield:write'])]
    private $tag;

    /**
     * @var string|null
     */
    #[Groups(['leadfield:read', 'leadfield:write'])]
    private $description;

    public ?int $deletedId;

    public function __construct(?string $tag = null, bool $clean = true)
    {
        $this->tag = $clean && $tag ? $this->validateTag($tag) : $tag;
    }

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('lead_tags')
            ->setCustomRepositoryClass(TagRepository::class)
            ->addIndex(['tag'], 'lead_tag_search');

        $builder->addId();
        $builder->addField('tag', Types::STRING);
        $builder->addNamedField('description', Types::TEXT, 'description', true);
        static::addUuidField($builder);
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
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @return Tag
     */
    public function setTag(string $tag)
    {
        $this->tag = $this->validateTag($tag);

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
     * @param string $description
     *
     * @return Tag
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    private function validateTag(string $tag): string
    {
        return InputHelper::string(trim((string) $tag));
    }
}
