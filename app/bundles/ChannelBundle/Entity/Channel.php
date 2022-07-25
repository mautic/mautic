<?php

namespace Mautic\ChannelBundle\Entity;

use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CommonEntity;

class Channel extends CommonEntity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $channel;

    /**
     * @var int
     */
    private $channelId;

    /**
     * @var string
     */
    private $channelName;

    /**
     * @var Message
     */
    private $message;

    /**
     * @var array
     */
    private $properties = [];

    /**
     * @var bool
     */
    private $isEnabled = false;

    public static function loadMetadata(ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('message_channels')
                ->addIndex(['channel', 'channel_id'], 'channel_entity_index')
                ->addIndex(['channel', 'is_enabled'], 'channel_enabled_index')
                ->addUniqueConstraint(['message_id', 'channel'], 'channel_index');

        $builder
            ->addId()
            ->addField('channel', 'string')
            ->addNamedField('channelId', 'integer', 'channel_id', true)
            ->addField('properties', 'json_array')
            ->createField('isEnabled', 'boolean')
                ->columnName('is_enabled')
                ->build();

        $builder->createManyToOne('message', Message::class, 'channels')
                ->addJoinColumn('message_id', 'id', false, false, 'CASCADE')
                ->inversedBy('channels')
                ->build();
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     *
     * @return Channel
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return int
     */
    public function getChannelId()
    {
        return $this->channelId;
    }

    /**
     * @param int $channelId
     *
     * @return Channel
     */
    public function setChannelId($channelId)
    {
        if (empty($channelId)) {
            $channelId = null;
        }

        $this->channelId = $channelId;

        return $this;
    }

    /**
     * @return string
     */
    public function getChannelName()
    {
        return $this->channelName;
    }

    /**
     * @param string $channelName
     *
     * @return Channel
     */
    public function setChannelName($channelName)
    {
        $this->channelName = $channelName;

        return $this;
    }

    /**
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return Channel
     */
    public function setMessage(Message $message)
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

    /**
     * @return Channel
     */
    public function setProperties(array $properties)
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
     *
     * @return Channel
     */
    public function setIsEnabled($isEnabled)
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }
}
