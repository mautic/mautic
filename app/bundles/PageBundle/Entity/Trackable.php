<?php

namespace Mautic\PageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class Trackable
{
    /**
     * @var Redirect
     */
    private $redirect;

    /**
     * @var string
     */
    private $channel;

    /**
     * @var int
     */
    private $channelId;

    /**
     * @var int
     */
    private $hits = 0;

    /**
     * @var int
     */
    private $uniqueHits = 0;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('channel_url_trackables')
            ->setCustomRepositoryClass(TrackableRepository::class)
            ->addIndex(['channel', 'channel_id'], 'channel_url_trackable_search');

        $builder->createManyToOne('redirect', Redirect::class)
            ->addJoinColumn('redirect_id', 'id', true, false, 'CASCADE')
            ->cascadePersist()
            ->inversedBy('trackables')
            ->isPrimaryKey()
            ->build();

        $builder->createField('channelId', 'integer')
            ->columnName('channel_id')
            ->makePrimaryKey()
            ->build();

        $builder->addField('channel', 'string');

        $builder->addField('hits', 'integer');

        $builder->addNamedField('uniqueHits', 'integer', 'unique_hits');
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('trackable')
            ->addListProperties(
                [
                    'redirect',
                    'channelId',
                    'channel',
                    'hits',
                    'uniqueHits',
                ]
            )
            ->build();
    }

    /**
     * @return Redirect
     */
    public function getRedirect()
    {
        return $this->redirect;
    }

    public function setRedirect(Redirect $redirect): static
    {
        $this->redirect = $redirect;

        return $this;
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
     */
    public function setChannel($channel): static
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
     */
    public function setChannelId($channelId): static
    {
        $this->channelId = $channelId;

        return $this;
    }

    /**
     * @return int
     */
    public function getHits()
    {
        return $this->hits;
    }

    /**
     * @param int $hits
     */
    public function setHits($hits): static
    {
        $this->hits = $hits;

        return $this;
    }

    /**
     * @return int
     */
    public function getUniqueHits()
    {
        return $this->uniqueHits;
    }

    /**
     * @param int $uniqueHits
     */
    public function setUniqueHits($uniqueHits): static
    {
        $this->uniqueHits = $uniqueHits;

        return $this;
    }
}
