<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
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
    private $id;

    /**
     * @var Lead|null
     */
    private $lead;

    /**
     * @var \DateTimeInterface
     */
    private $dateAdded;

    /**
     * @var int
     */
    #[ORM\Column(type: 'smallint')]
    private $reason = 0;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private $comments;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 191)]
    private $channel;

    private $channelId;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->addId();

        $builder->addLead(true, 'CASCADE', false, 'doNotContact');

        $builder->addDateAdded();

        $builder->addNamedField('channelId', 'integer', 'channel_id', true);
    }

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
