<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Webmecanik
 * @link        http://webmecanik.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FeedBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class Snapshot
{

    /**
     * @var int
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $date;

    /**
     * @var string
     */
    private $xmlString;

    /**
     * @var Feed
     */
    private $feed;

    /**
     * @var bool
     */
    private $isExpired = false;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('feed_snapshots');

        $builder->addId();

        $builder->createField('date', 'datetime')
            ->columnName('date')
            ->build();

        $builder->createField('xmlString', 'text')
            ->columnName('xml_string')
            ->build();

        $builder->createManyToOne('feed', 'Feed')
            ->addJoinColumn('feed_id', 'id', false)
            ->build();

        $builder->createField('isExpired', 'boolean')
            ->columnName('is_expired')
            ->build();

    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate(\DateTime $date)
    {
        $this->date = $date;
        return $this;
    }

    public function getXmlString()
    {
        return $this->xmlString;
    }

    public function setXmlString($xmlString)
    {
        $this->xmlString = $xmlString;
        return $this;
    }

    public function getFeed()
    {
        return $this->feed;
    }

    public function setFeed(Feed $feed)
    {
        $this->feed = $feed;
        return $this;
    }

    public function getIsExpired()
    {
        return $this->isExpired;
    }

    public function setIsExpired($isExpired)
    {
        $this->isExpired = $isExpired;
        return $this;
    }

    public function isExpired()
    {
        return $this->getIsExpired();
    }

}
