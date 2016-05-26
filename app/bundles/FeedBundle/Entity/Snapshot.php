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
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata (ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('feed_snapshots');

//         $builder->addId();

//         $builder->createOneToOne('email', 'Mautic\EmailBundle\Entity\Email')
//             ->inversedBy('feed')
//             ->addJoinColumn('email_id', 'id', false)
//             ->build();

//         $builder->createField('feedUrl', 'string')
//             ->columnName('feed_url')
//             ->build();

//         $builder->createField('itemCount', 'integer')
//             ->columnName('item_count')
//             ->build();
    }

}
