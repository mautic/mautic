<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Class Log.
 */
class Log
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var Webhook
     */
    private $webhook;
    /**
     * @var string
     */
    private $statusCode;
    /**
     * @var \DateTime
     */
    private $dateAdded;
    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('webhook_logs')
            ->setCustomRepositoryClass('Mautic\WebhookBundle\Entity\LogRepository');
        // id columns
        $builder->addId();
        // M:1 for webhook
        $builder->createManyToOne('webhook', 'Webhook')
            ->inversedBy('logs')
            ->addJoinColumn('webhook_id', 'id', false, false, 'CASCADE')
            ->build();
        // status code
        $builder->createField('statusCode', 'string')
            ->columnName('status_code')
            ->length(50)
            ->build();
        // date added
        $builder->createField('dateAdded', 'datetime')
            ->columnName('date_added')
            ->nullable()
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
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
    /**
     * @return mixed
     */
    public function getWebhook()
    {
        return $this->webhook;
    }
    /**
     * @param mixed $webhook
     */
    public function setWebhook($webhook)
    {
        $this->webhook = $webhook;

        return $this;
    }
    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
    /**
     * @param mixed $statusCode
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }
    /**
     * @return mixed
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }
    /**
     * @param mixed $dateAdded
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }
}
