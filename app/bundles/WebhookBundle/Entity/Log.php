<?php

namespace Mautic\WebhookBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

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
     * @var \DateTimeInterface
     */
    private $dateAdded;

    /**
     * @var float|null
     */
    private $runtime;

    private ?string $note = null;

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('webhook_logs')
            ->setCustomRepositoryClass(LogRepository::class)
            ->addIndex(['webhook_id', 'date_added'], 'webhook_id_date_added')
            ->addId();

        $builder->createManyToOne('webhook', 'Webhook')
            ->inversedBy('logs')
            ->addJoinColumn('webhook_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->createField('statusCode', Types::STRING)
            ->columnName('status_code')
            ->length(50)
            ->build();

        $builder->addNullableField('dateAdded', Types::DATETIME_MUTABLE, 'date_added');
        $builder->addNullableField('note', Types::STRING);
        $builder->addNullableField('runtime', Types::FLOAT);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Webhook
     */
    public function getWebhook()
    {
        return $this->webhook;
    }

    /**
     * @return Log
     */
    public function setWebhook(Webhook $webhook)
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
     *
     * @return Log
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @return Log
     */
    public function setDateAdded(\DateTime $dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    /**
     * Strips tags and keeps first 191 characters so it would fit in the varchar 191 limit.
     */
    public function setNote(?string $note): self
    {
        $this->note = $note ? substr(strip_tags(iconv('UTF-8', 'UTF-8//IGNORE', $note)), 0, 190) : $note;

        return $this;
    }

    /**
     * @return float
     */
    public function getRuntime()
    {
        return $this->runtime;
    }

    /**
     * @param float $runtime
     *
     * @return Log
     */
    public function setRuntime($runtime)
    {
        $this->runtime = round($runtime, 2);

        return $this;
    }
}
