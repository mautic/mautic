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
use Mautic\EmailBundle\Entity\Email;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class Feed
{

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $feedUrl;

    /**
     * @var int
     */
    private $itemCount;

    /**
     * @var Email
     */
    private $email;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata (ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('feed');

        $builder->addId();

        $builder->createOneToOne('email', 'Mautic\EmailBundle\Entity\Email')
            ->inversedBy('feed')
            ->addJoinColumn('email_id', 'id', false)
            ->build();

        $builder->createField('feedUrl', 'string')
            ->columnName('feed_url')
            ->build();

        $builder->createField('itemCount', 'integer')
            ->columnName('item_count')
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

    public function getFeedUrl()
    {
        return $this->feedUrl;
    }

    public function setFeedUrl($feedUrl)
    {
        $this->feedUrl = $feedUrl;
        return $this;
    }

    public function getItemCount()
    {
        return $this->itemCount;
    }

    public function setItemCount($itemCount)
    {
        $this->itemCount = $itemCount;
        return $this;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail(Email $email)
    {
        $this->email = $email;
        return $this;
    }


}
