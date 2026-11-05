<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Ramsey\Uuid\Uuid;

#[ORM\Entity(repositoryClass: EmailReplyRepository::class)]
#[ORM\Table(name: 'email_stat_replies')]
#[ORM\Index(name: 'email_replies', columns: ['stat_id', 'message_id'])]
#[ORM\Index(name: 'date_email_replied', columns: ['date_replied'])]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class EmailReply
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private readonly string $id;

    #[ORM\Column(name: 'date_replied', type: 'datetime')]
    private readonly \DateTimeInterface $dateReplied;

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
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

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Stat::class, inversedBy: 'replies')]
        #[ORM\JoinColumn(name: 'stat_id', nullable: false, onDelete: 'CASCADE')]
        private readonly Stat $stat,
        #[ORM\Column(name: 'message_id', type: 'string', length: 191)]
        private readonly ?string $messageId,
        ?\DateTime $dateReplied = null,
    ) {
        $this->id          = Uuid::uuid4()->toString();
        $this->dateReplied = $dateReplied ?? new \DateTime();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getStat(): Stat
    {
        return $this->stat;
    }

    public function getDateReplied(): \DateTimeInterface
    {
        return $this->dateReplied;
    }

    public function getMessageId(): ?string
    {
        return $this->messageId;
    }
}
