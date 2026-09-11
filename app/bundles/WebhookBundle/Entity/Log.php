<?php

namespace Mautic\WebhookBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Log
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var Webhook
     */
    #[ORM\ManyToOne(targetEntity: Webhook::class, inversedBy: 'logs')]
    #[ORM\JoinColumn(name: 'webhook_id', nullable: false, onDelete: 'CASCADE')]
    private $webhook;

    /**
     * @var string
     */
    #[ORM\Column(name: 'status_code', type: Types::STRING, length: 50)]
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

        $builder->addNullableField('dateAdded', Types::DATETIME_MUTABLE, 'date_added');
        $builder->addNullableField('note', Types::STRING);
        $builder->addNullableField('runtime', Types::FLOAT);
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Webhook|null
     */
    public function getWebhook()
    {
        return $this->webhook;
    }

    public function setWebhook(Webhook $webhook): static
    {
        $this->webhook = $webhook;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param mixed $statusCode
     */
    public function setStatusCode($statusCode): static
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    public function setDateAdded(\DateTime $dateAdded): static
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
     * @return float|null
     */
    public function getRuntime()
    {
        return $this->runtime;
    }

    /**
     * @param float $runtime
     */
    public function setRuntime($runtime): static
    {
        $this->runtime = round($runtime, 2);

        return $this;
    }
}
