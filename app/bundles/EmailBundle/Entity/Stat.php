<?php

namespace Mautic\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;

class Stat
{
    /**
     * @var int Limit number of stored 'openDetails'
     */
    public const MAX_OPEN_DETAILS = 1000;

    /**
     * @var string|null
     */
    private $id;

    /**
     * @var Email|null
     */
    private $email;

    /**
     * @var Lead|null
     */
    private $lead;

    /**
     * @var string
     */
    private $emailAddress;

    /**
     * @var LeadList|null
     */
    private $list;

    /**
     * @var IpAddress|null
     */
    private $ipAddress;

    /**
     * @var \DateTimeInterface|null
     */
    private $dateSent;

    /**
     * @var bool
     */
    private $isRead = false;

    /**
     * @var bool
     */
    private $isFailed = false;

    /**
     * @var bool
     */
    private $viewedInBrowser = false;

    /**
     * @var \DateTimeInterface|null
     */
    private $dateRead;

    /**
     * @var string|null
     */
    private $trackingHash;

    /**
     * @var int|null
     */
    private $retryCount = 0;

    /**
     * @var string|null
     */
    private $source;

    /**
     * @var int|null
     */
    private $sourceId;

    /**
     * @var array
     */
    private $tokens = [];

    /**
     * @var Copy|null
     */
    private $storedCopy;

    /**
     * @var int|null
     */
    private $openCount = 0;

    /**
     * @var \DateTimeInterface|null
     */
    private $lastOpened;

    /**
     * @var array
     */
    private $openDetails = [];

    /**
     * @var ArrayCollection|EmailReply[]
     */
    private $replies;

    public function __construct()
    {
        $this->replies = new ArrayCollection();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('email_stats')
            ->setCustomRepositoryClass(\Mautic\EmailBundle\Entity\StatRepository::class)
            ->addIndex(['email_id', 'lead_id'], 'stat_email_search')
            ->addIndex(['lead_id', 'email_id'], 'stat_email_search2')
            ->addIndex(['is_failed'], 'stat_email_failed_search')
            ->addIndex(['is_read', 'date_sent'], 'is_read_date_sent')
            ->addIndex(['tracking_hash'], 'stat_email_hash_search')
            ->addIndex(['source', 'source_id'], 'stat_email_source_search')
            ->addIndex(['date_sent'], 'email_date_sent')
            ->addIndex(['date_read', 'lead_id'], 'email_date_read_lead');

        $builder->addBigIntIdField();

        $builder->createManyToOne('email', 'Email')
            ->inversedBy('stats')
            ->addJoinColumn('email_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->addLead(true, 'SET NULL');

        $builder->createField('emailAddress', 'string')
            ->columnName('email_address')
            ->build();

        $builder->createManyToOne('list', \Mautic\LeadBundle\Entity\LeadList::class)
            ->addJoinColumn('list_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->addIpAddress(true);

        $builder->createField('dateSent', 'datetime')
            ->columnName('date_sent')
            ->build();

        $builder->createField('isRead', 'boolean')
            ->columnName('is_read')
            ->build();

        $builder->createField('isFailed', 'boolean')
            ->columnName('is_failed')
            ->build();

        $builder->createField('viewedInBrowser', 'boolean')
            ->columnName('viewed_in_browser')
            ->build();

        $builder->createField('dateRead', 'datetime')
            ->columnName('date_read')
            ->nullable()
            ->build();

        $builder->createField('trackingHash', 'string')
            ->columnName('tracking_hash')
            ->nullable()
            ->build();

        $builder->createField('retryCount', 'integer')
            ->columnName('retry_count')
            ->nullable()
            ->build();

        $builder->createField('source', 'string')
            ->nullable()
            ->build();

        $builder->createField('sourceId', 'integer')
            ->columnName('source_id')
            ->nullable()
            ->build();

        $builder->createField('tokens', 'array')
            ->nullable()
            ->build();

        $builder->createManyToOne('storedCopy', \Mautic\EmailBundle\Entity\Copy::class)
            ->addJoinColumn('copy_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->addNullableField('openCount', 'integer', 'open_count');

        $builder->addNullableField('lastOpened', 'datetime', 'last_opened');

        $builder->addNullableField('openDetails', 'array', 'open_details');

        $builder->createOneToMany('replies', EmailReply::class)
            ->mappedBy('stat')
            ->fetchExtraLazy()
            ->cascadeAll()
            ->build();
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('stat')
            ->addProperties(
                [
                    'id',
                    'emailAddress',
                    'ipAddress',
                    'dateSent',
                    'isRead',
                    'isFailed',
                    'dateRead',
                    'retryCount',
                    'source',
                    'openCount',
                    'lastOpened',
                    'sourceId',
                    'trackingHash',
                    'viewedInBrowser',
                    'lead',
                    'email',
                ]
            )
            ->build();
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateRead()
    {
        return $this->dateRead;
    }

    /**
     * @param \DateTime|null $dateRead
     */
    public function setDateRead($dateRead): void
    {
        $this->dateRead = $dateRead;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateSent()
    {
        return $this->dateSent;
    }

    /**
     * @param \DateTime|null $dateSent
     */
    public function setDateSent($dateSent): void
    {
        $this->dateSent = $dateSent;
    }

    /**
     * @return Email|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail(Email $email = null): void
    {
        $this->email = $email;
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * @return IpAddress|null
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * @param IpAddress|null $ip
     */
    public function setIpAddress(IpAddress $ip): void
    {
        $this->ipAddress = $ip;
    }

    /**
     * @return bool
     */
    public function getIsRead()
    {
        return $this->isRead;
    }

    /**
     * @return bool
     */
    public function isRead()
    {
        return $this->getIsRead();
    }

    /**
     * @param bool $isRead
     */
    public function setIsRead($isRead): void
    {
        $this->isRead = $isRead;
    }

    /**
     * @return Lead|null
     */
    public function getLead()
    {
        return $this->lead;
    }

    public function setLead(Lead $lead = null): void
    {
        $this->lead = $lead;
    }

    /**
     * @return string|null
     */
    public function getTrackingHash()
    {
        return $this->trackingHash;
    }

    /**
     * @param string|null $trackingHash
     */
    public function setTrackingHash($trackingHash): void
    {
        $this->trackingHash = $trackingHash;
    }

    /**
     * @return LeadList|null
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param LeadList|null $list
     */
    public function setList($list): void
    {
        $this->list = $list;
    }

    /**
     * @return int
     */
    public function getRetryCount()
    {
        return $this->retryCount;
    }

    /**
     * @param int $retryCount
     */
    public function setRetryCount($retryCount): void
    {
        $this->retryCount = $retryCount;
    }

    /**
     * Increase the retry count.
     */
    public function upRetryCount(): void
    {
        ++$this->retryCount;
    }

    /**
     * @return bool
     */
    public function getIsFailed()
    {
        return $this->isFailed;
    }

    /**
     * @param bool $isFailed
     */
    public function setIsFailed($isFailed): void
    {
        $this->isFailed = $isFailed;
    }

    /**
     * @return bool
     */
    public function isFailed()
    {
        return $this->getIsFailed();
    }

    /**
     * @return string|null
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * @param string|null $emailAddress
     */
    public function setEmailAddress($emailAddress): void
    {
        $this->emailAddress = $emailAddress;
    }

    /**
     * @return bool
     */
    public function getViewedInBrowser()
    {
        return $this->viewedInBrowser;
    }

    /**
     * @param bool $viewedInBrowser
     */
    public function setViewedInBrowser($viewedInBrowser): void
    {
        $this->viewedInBrowser = $viewedInBrowser;
    }

    /**
     * @return string|null
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string|null $source
     */
    public function setSource($source): void
    {
        $this->source = $source;
    }

    /**
     * @return int|null
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * @param int|null $sourceId
     */
    public function setSourceId($sourceId): void
    {
        $this->sourceId = (int) $sourceId;
    }

    /**
     * @return array
     */
    public function getTokens()
    {
        return $this->tokens;
    }

    public function setTokens(array $tokens): void
    {
        $this->tokens = $tokens;
    }

    /**
     * @return int
     */
    public function getOpenCount()
    {
        return $this->openCount;
    }

    /**
     * @param int $openCount
     *
     * @return Stat
     */
    public function setOpenCount($openCount)
    {
        $this->openCount = $openCount;

        return $this;
    }

    /**
     * @param string $details
     */
    public function addOpenDetails($details): void
    {
        if (self::MAX_OPEN_DETAILS > $this->getOpenCount()) {
            $this->openDetails[] = $details;
        }

        ++$this->openCount;
    }

    /**
     * Up the sent count.
     *
     * @return Stat
     */
    public function upOpenCount()
    {
        $count           = (int) $this->openCount + 1;
        $this->openCount = $count;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getLastOpened()
    {
        return $this->lastOpened;
    }

    /**
     * @param \DateTime|null $lastOpened
     *
     * @return Stat
     */
    public function setLastOpened($lastOpened)
    {
        $this->lastOpened = $lastOpened;

        return $this;
    }

    /**
     * @return array
     */
    public function getOpenDetails()
    {
        return $this->openDetails;
    }

    /**
     * @return Stat
     */
    public function setOpenDetails(array $openDetails)
    {
        $this->openDetails = $openDetails;

        return $this;
    }

    /**
     * @return Copy|null
     */
    public function getStoredCopy()
    {
        return $this->storedCopy;
    }

    /**
     * @return Stat
     */
    public function setStoredCopy(Copy $storedCopy)
    {
        $this->storedCopy = $storedCopy;

        return $this;
    }

    /**
     * @return ArrayCollection|EmailReply[]
     */
    public function getReplies()
    {
        return $this->replies;
    }

    public function addReply(EmailReply $reply): void
    {
        $this->replies[] = $reply;
    }
}
