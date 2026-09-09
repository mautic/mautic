<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Helper\InputHelper;

#[ORM\Entity(repositoryClass: DoNotContactRepository::class)]
#[ORM\Table(name: 'lead_donotcontact')]
#[ORM\Index(columns: ['lead_id', 'channel', 'reason'], name: 'leadid_reason_channel')]
#[ORM\Index(columns: ['reason'], name: 'dnc_reason_search')]
#[ORM\Index(columns: ['date_added'], name: 'dnc_date_added')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class DoNotContact
{
    /**
     * Lead is contactable.
     */
    public const IS_CONTACTABLE = 0;

    /**
     * Lead unsubscribed themselves.
     */
    public const UNSUBSCRIBED = 1;

    /**
     * Lead was unsubscribed due to an unsuccessful send.
     */
    public const BOUNCED = 2;

    /**
     * Lead was manually unsubscribed by user.
     */
    public const MANUAL = 3;

    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\ManyToOne(targetEntity: \Mautic\LeadBundle\Entity\Lead::class, inversedBy: 'doNotContact')]
    #[ORM\JoinColumn(name: 'lead_id', onDelete: 'CASCADE')]
    private ?\Mautic\LeadBundle\Entity\Lead $lead = null;

    #[ORM\Column(name: 'date_added', type: 'datetime')]
    private ?\DateTime $dateAdded = null;

    /**
     * @var int
     */
    #[ORM\Column(type: 'smallint')]
    private $reason = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comments = null;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 191)]
    private $channel;

    #[ORM\Column(name: 'channel_id', type: 'integer', nullable: true)]
    private $channelId;

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('doNotContact')
            ->addListProperties(
                [
                    'id',
                    'dateAdded',
                    'reason',
                    'comments',
                    'channel',
                    'channelId',
                ]
            )
            ->addProperties(
                [
                    'lead',
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
     * @return Lead|null
     */
    public function getLead()
    {
        return $this->lead;
    }

    public function setLead(Lead $lead): static
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * @return \DateTimeInterface
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

    /**
     * @return int
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @param int $reason
     */
    public function setReason($reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getComments()
    {
        return $this->comments;
    }

    public function setComments(?string $comments): static
    {
        $this->comments = InputHelper::string((string) $comments);

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
     */
    public function setChannel($channel): static
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getChannelId()
    {
        return $this->channelId;
    }

    /**
     * @param mixed $channelId
     */
    public function setChannelId($channelId): static
    {
        $this->channelId = $channelId;

        return $this;
    }
}
