<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;

#[ORM\Entity(repositoryClass: TrackableRepository::class)]
#[ORM\Table(name: 'channel_url_trackables')]
#[ORM\Index(columns: ['channel', 'channel_id'], name: 'channel_url_trackable_search')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Trackable
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Redirect::class, inversedBy: 'trackables', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'redirect_id', onDelete: 'CASCADE')]
    private ?\Mautic\PageBundle\Entity\Redirect $redirect = null;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 191)]
    private $channel;

    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(name: 'channel_id', type: 'integer')]
    private $channelId;

    /**
     * @var int
     */
    #[ORM\Column(type: 'integer')]
    private $hits = 0;

    /**
     * @var int
     */
    #[ORM\Column(name: 'unique_hits', type: 'integer')]
    private $uniqueHits = 0;

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
