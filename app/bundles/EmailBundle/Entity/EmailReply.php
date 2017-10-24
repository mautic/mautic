<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Class EmailReply.
 */
class EmailReply
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var Stat
     */
    private $stat;

    /**
     * @var \DateTime
     */
    private $dateReplied;

    /**
     * @var string
     */
    private $messageId;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('email_stat_replies')
            ->addIndex(['stat_id', 'message_id'], 'email_replies')
            ->addIndex(['date_replied'], 'date_email_replied');

        $builder->addUuid();

        $builder->createManyToOne('stat', Stat::class)
            ->inversedBy('replies')
            ->addJoinColumn('stat_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->createField('dateReplied', 'datetime')
            ->columnName('date_replied')
            ->build();

        $builder->createField('messageId', 'string')
            ->columnName('message_id')
            ->build();
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('emailReply')
            ->addProperties(
                [
                    'uuid',
                    'dateReplied',
                    'messageId',
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
     * @return Stat
     */
    public function getStat()
    {
        return $this->stat;
    }

    /**
     * @param Stat $stat
     *
     * @return EmailReply
     */
    public function setStat(Stat $stat)
    {
        $this->stat = $stat;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateReplied()
    {
        return $this->dateReplied;
    }

    /**
     * @param \DateTime $dateReplied
     *
     * @return EmailReply
     */
    public function setDateReplied(\DateTime $dateReplied)
    {
        $this->dateReplied = $dateReplied;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * @param string $messageId
     *
     * @return EmailReply
     */
    public function setMessageId($messageId)
    {
        $this->messageId = $messageId;

        return $this;
    }
}
