<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CommonEntity;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('channel:messages:viewown')"),
        new Post(security: "is_granted('channel:messages:create')"),
        new Get(security: "is_granted('channel:messages:viewown', object)"),
        new Put(security: "is_granted('channel:messages:editown', object)"),
        new Patch(security: "is_granted('channel:messages:editother', object)"),
        new Delete(security: "is_granted('channel:messages:deleteown', object)"),
    ],
    normalizationContext: [
        'groups'                  => ['channel:read'],
        'swagger_definition_name' => 'Read',
        'api_included'            => ['message'],
    ],
    denormalizationContext: [
        'groups'                  => ['channel:write'],
        'swagger_definition_name' => 'Write',
    ]
)]
#[ORM\Entity]
#[ORM\Table(name: 'message_channels')]
#[ORM\Index(columns: ['channel', 'channel_id'], name: 'channel_entity_index')]
#[ORM\Index(columns: ['channel', 'is_enabled'], name: 'channel_enabled_index')]
#[ORM\UniqueConstraint(columns: ['message_id', 'channel'], name: 'channel_index')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Channel extends CommonEntity implements UuidInterface
{
    use UuidTrait;

    /**
     * @var int
     */
    #[Groups(['channel:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var string
     */
    #[Groups(['channel:read', 'channel:write', 'message:read'])]
    #[ORM\Column(type: 'string', length: 191)]
    private $channel;

    /**
     * @var int|null
     */
    #[Groups(['channel:read', 'channel:write'])]
    #[ORM\Column(name: 'channel_id', type: 'integer', nullable: true)]
    private $channelId;

    /**
     * @var string
     */
    #[Groups(['channel:read', 'message:read'])]
    private $channelName;

    /**
     * @var Message
     */
    #[Groups(['channel:read', 'channel:write'])]
    private $message;

    /**
     * @var array
     */
    #[Groups(['channel:read', 'channel:write'])]
    #[ORM\Column(type: Types::JSON)]
    private $properties = [];

    /**
     * @var bool
     */
    #[Groups(['channel:read', 'channel:write', 'message:read'])]
    #[ORM\Column(name: 'is_enabled', type: 'boolean')]
    private $isEnabled = false;

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->createManyToOne('message', Message::class)
                ->addJoinColumn('message_id', 'id', false, false, 'CASCADE')
                ->inversedBy('channels')
                ->isOwnershipParent()
                ->build();
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('messageChannel')
            ->addListProperties(
                [
                    'id',
                    'channel',
                    'channelId',
                    'channelName',
                    'isEnabled',
                ]
            )
            ->addProperties(
                [
                    'properties',
                    'message',
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
     * @return string|null
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     */
    public function setChannel($channel): static
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getChannelId()
    {
        return $this->channelId;
    }

    /**
     * @param int $channelId
     */
    public function setChannelId($channelId): static
    {
        if (empty($channelId)) {
            $channelId = null;
        }

        $this->channelId = $channelId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getChannelName()
    {
        return $this->channelName;
    }

    /**
     * @param string $channelName
     */
    public function setChannelName($channelName): static
    {
        $this->channelName = $channelName;

        return $this;
    }

    /**
     * @return Message|null
     */
    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage(Message $message): static
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    public function setProperties(array $properties): static
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->isEnabled;
    }

    /**
     * @param bool $isEnabled
     */
    public function setIsEnabled($isEnabled): static
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    public function getPermissionUser(): mixed
    {
        return $this->message->getCreatedBy();
    }
}
