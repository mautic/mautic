<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Class Trackable.
 */
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

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('channel_url_trackables')
            ->setCustomRepositoryClass('Mautic\PageBundle\Entity\TrackableRepository')
            ->addIndex(['channel', 'channel_id'], 'channel_url_trackable_search');

        $builder->createManyToOne('redirect', 'Mautic\PageBundle\Entity\Redirect')
            ->addJoinColumn('redirect_id', 'id', true, false, 'CASCADE')
            ->cascadePersist()
            ->inversedBy('trackables')
            ->isPrimaryKey()
            ->build();

        $builder->createField('channelId', 'integer')
            ->columnName('channel_id')
            ->isPrimaryKey()
            ->build();

        $builder->addField('channel', 'string');

        $builder->addField('hits', 'integer');

        $builder->addNamedField('uniqueHits', 'integer', 'unique_hits');
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
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

    /**
     * @param Redirect $redirect
     *
     * @return Trackable
     */
    public function setRedirect(Redirect $redirect)
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
     *
     * @return Trackable
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
     * @return Trackable
     */
    public function setChannelId($channelId)
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
     *
     * @return Trackable
     */
    public function setHits($hits)
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
     *
     * @return Trackable
     */
    public function setUniqueHits($uniqueHits)
    {
        $this->uniqueHits = $uniqueHits;

        return $this;
    }
}
