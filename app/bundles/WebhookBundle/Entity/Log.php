<?php

namespace Mautic\WebhookBundle\Entity;

use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: LogRepository::class)]
#[ORM\Table(name: 'webhook_logs')]
#[ORM\Index(columns: ['webhook_id', 'date_added'], name: 'webhook_id_date_added')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Log
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\ManyToOne(targetEntity: 'Webhook', inversedBy: 'logs')]
    #[ORM\JoinColumn(name: 'webhook_id', nullable: false, onDelete: 'CASCADE')]
    private ?\Mautic\WebhookBundle\Entity\Webhook $webhook = null;

    /**
     * @var string
     */
    #[ORM\Column(name: 'status_code', type: Types::STRING, length: 50)]
    private $statusCode;

    #[ORM\Column(name: 'date_added', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $dateAdded = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $runtime = null;

    #[ORM\Column(type: Types::STRING, length: 191, nullable: true)]
    private ?string $note = null;

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    public function getWebhook(): ?\Mautic\WebhookBundle\Entity\Webhook
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

    public function getDateAdded(): ?\DateTime
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

    public function getRuntime(): ?float
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
