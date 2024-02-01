<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Helper\InputHelper;

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
    private $reason = 0;

    /**
     * @var string|null
     */
    private $comments;

    /**
     * @var string
     */
    private $channel;

    private $channelId;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('lead_donotcontact')
            ->setCustomRepositoryClass(\Mautic\LeadBundle\Entity\DoNotContactRepository::class)
            ->addIndex(['reason'], 'dnc_reason_search');

        $builder->addId();

        $builder->addLead(true, 'CASCADE', false, 'doNotContact');

        $builder->addDateAdded();

        $builder->createField('reason', 'smallint')
            ->build();

        $builder->createField('channel', 'string')
            ->build();

        $builder->addNamedField('channelId', 'integer', 'channel_id', true);

        $builder->createField('comments', 'text')
            ->nullable()
            ->build();
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
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return DoNotContact
     */
    public function setLead(Lead $lead)
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

    /**
     * @return DoNotContact
     */
    public function setDateAdded(\DateTime $dateAdded)
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
     *
     * @return DoNotContact
     */
    public function setReason($reason)
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * @return string
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @param string $comments
     *
     * @return DoNotContact
     */
    public function setComments($comments)
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
     *
     * @return DoNotContact
     */
    public function setChannel($channel)
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
     *
     * @return DoNotContact
     */
    public function setChannelId($channelId)
    {
        $this->channelId = $channelId;

        return $this;
    }
}
