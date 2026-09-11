<?php

namespace Mautic\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;

#[ORM\Entity(repositoryClass: StatRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\Index(columns: ['email_id', 'lead_id'], name: 'stat_email_search')]
#[ORM\Index(columns: ['lead_id', 'email_id'], name: 'stat_email_search2')]
#[ORM\Index(columns: ['is_failed'], name: 'stat_email_failed_search')]
#[ORM\Index(columns: ['is_read', 'date_sent'], name: 'is_read_date_sent')]
#[ORM\Index(columns: ['tracking_hash'], name: 'stat_email_hash_search')]
#[ORM\Index(columns: ['source', 'source_id'], name: 'stat_email_source_search')]
#[ORM\Index(columns: ['date_sent'], name: 'email_date_sent')]
#[ORM\Index(columns: ['date_read', 'lead_id'], name: 'email_date_read_lead')]
#[ORM\Index(columns: ['lead_id', 'date_sent'], name: 'stat_email_lead_id_date_sent')]
#[ORM\Index(columns: ['email_id', 'is_read'], name: 'stat_email_email_id_is_read')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Stat
{
    /**
     * @var int Limit number of stored 'openDetails'
     */
    public const MAX_OPEN_DETAILS = 1000;

    public const TABLE_NAME = 'email_stats';

    private ?string $id = null;

    /**
     * @var Email|null
     */
    #[ORM\ManyToOne(targetEntity: Email::class, inversedBy: 'stats')]
    #[ORM\JoinColumn(name: 'email_id', onDelete: 'SET NULL')]
    private $email;

    /**
     * @var Lead|null
     */
    private $lead;

    /**
     * @var string
     */
    #[ORM\Column(name: 'email_address', type: 'string', length: 191)]
    private $emailAddress;

    /**
     * @var LeadList|null
     */
    #[ORM\ManyToOne(targetEntity: LeadList::class)]
    #[ORM\JoinColumn(name: 'list_id', onDelete: 'SET NULL')]
    private $list;

    private ?IpAddress $ipAddress = null;

    #[ORM\Column(name: 'date_sent', type: 'datetime')]
    private ?\DateTimeInterface $dateSent = null;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'is_read', type: 'boolean')]
    private $isRead = false;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'is_failed', type: 'boolean')]
    private $isFailed = false;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'viewed_in_browser', type: 'boolean')]
    private $viewedInBrowser = false;

    /**
     * @var \DateTimeInterface|null
     */
    #[ORM\Column(name: 'date_read', type: 'datetime', nullable: true)]
    private $dateRead;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'tracking_hash', type: 'string', length: 191, nullable: true)]
    private $trackingHash;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'retry_count', type: 'integer', nullable: true)]
    private $retryCount = 0;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 191, nullable: true)]
    private $source;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'source_id', type: 'integer', nullable: true)]
    private $sourceId;

    /**
     * @var array
     */
    #[ORM\Column(type: 'array', nullable: true)]
    private $tokens = [];

    /**
     * @var Copy|null
     */
    #[ORM\ManyToOne(targetEntity: Copy::class)]
    #[ORM\JoinColumn(name: 'copy_id', onDelete: 'SET NULL')]
    private $storedCopy;

    /**
     * @var int|null
     */
    private $openCount = 0;

    private ?\DateTimeInterface $lastOpened = null;

    /**
     * @var array
     */
    private $openDetails = [];

    /**
     * @var ArrayCollection|EmailReply[]
     */
    #[ORM\OneToMany(mappedBy: 'stat', targetEntity: EmailReply::class, cascade: ['all'], fetch: 'EXTRA_LAZY')]
    private $replies;

    /**
     * @var array<string,mixed[]>
     */
    private array $changes = [];

    public function __construct()
    {
        $this->replies = new ArrayCollection();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->addBigIntIdField();

        $builder->addLead(true, 'SET NULL');

        $builder->addIpAddress(true);

        $builder->addNullableField('openCount', 'integer', 'open_count');

        $builder->addNullableField('lastOpened', 'datetime', 'last_opened');

        $builder->addNullableField('openDetails', 'array', 'open_details');
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

    public function getDateRead(): ?\DateTimeInterface
    {
        return $this->dateRead;
    }

    public function setDateRead(?\DateTimeInterface $dateRead): void
    {
        $dateRead = $this->toDateTime($dateRead);
        $this->addChange('dateRead', $this->dateRead, $dateRead);
        $this->dateRead = $dateRead;
    }

    public function getDateSent(): ?\DateTimeInterface
    {
        return $this->dateSent;
    }

    public function setDateSent(?\DateTimeInterface $dateSent): void
    {
        $dateSent = $this->toDateTime($dateSent);
        $this->addChange('dateSent', $this->dateSent, $dateSent);
        $this->dateSent = $dateSent;
    }

    /**
     * @return Email|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail(?Email $email = null): void
    {
        $this->email = $email;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getIpAddress(): ?IpAddress
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?IpAddress $ip): void
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
        return $this->isRead;
    }

    /**
     * @param bool $isRead
     */
    public function setIsRead($isRead): void
    {
        $this->addChange('isRead', $this->isRead, $isRead);
        $this->isRead = $isRead;
    }

    /**
     * @return Lead|null
     */
    public function getLead()
    {
        return $this->lead;
    }

    public function setLead(?Lead $lead = null): void
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
        $this->addChange('retryCount', $this->retryCount, $retryCount);
        $this->retryCount = $retryCount;
    }

    /**
     * Increase the retry count.
     */
    public function upRetryCount(): void
    {
        $this->addChange('retryCount', $this->retryCount, $this->retryCount + 1);
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
        $this->addChange('isFailed', $this->isFailed, $isFailed);
        $this->isFailed = $isFailed;
    }

    /**
     * @return bool
     */
    public function isFailed()
    {
        return $this->isFailed;
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
        $this->addChange('emailAddress', $this->emailAddress, $emailAddress);
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
        $this->addChange('viewedInBrowser', $this->viewedInBrowser, $viewedInBrowser);
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
        $this->addChange('source', $this->source, $source);
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
        $this->addChange('sourceId', $this->sourceId, (int) $sourceId);
        $this->sourceId = (int) $sourceId;
    }

    /**
     * @return array|null
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
     */
    public function setOpenCount($openCount): static
    {
        $this->addChange('openCount', $this->openCount, $openCount);
        $this->openCount = $openCount;

        return $this;
    }

    /**
     * @param string $details
     */
    public function addOpenDetails($details): void
    {
        if (self::MAX_OPEN_DETAILS > $this->openCount) {
            $this->openDetails[] = $details;
        }

        ++$this->openCount;
    }

    /**
     * Up the sent count.
     */
    public function upOpenCount(): static
    {
        $count = (int) $this->openCount + 1;
        $this->addChange('openCount', $this->openCount, $count);
        $this->openCount = $count;

        return $this;
    }

    public function getLastOpened(): ?\DateTimeInterface
    {
        return $this->lastOpened;
    }

    public function setLastOpened(?\DateTimeInterface $lastOpened): self
    {
        $lastOpened = $this->toDateTime($lastOpened);
        $this->addChange('lastOpened', $this->lastOpened, $lastOpened);
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

    public function setOpenDetails(array $openDetails): static
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

    public function setStoredCopy(Copy $storedCopy): static
    {
        $this->storedCopy = $storedCopy;

        return $this;
    }

    /**
     * @return ArrayCollection<int, EmailReply>
     */
    public function getReplies()
    {
        return $this->replies;
    }

    public function addReply(EmailReply $reply): void
    {
        $this->addChange('replyAdded', false, true);
        $this->replies[] = $reply;
    }

    /**
     * @return array<string,mixed[]>
     */
    public function getChanges(): array
    {
        return $this->changes;
    }

    /**
     * @param mixed $currentValue
     * @param mixed $newValue
     */
    private function addChange(string $property, $currentValue, $newValue): void
    {
        if ($currentValue === $newValue) {
            return;
        }

        $this->changes[$property] = [$currentValue, $newValue];
    }

    /**
     * @param \DateTime|\DateTimeImmutable|null $dateTime
     */
    private function toDateTime(?\DateTimeInterface $dateTime): ?\DateTime
    {
        return $dateTime instanceof \DateTimeImmutable ? \DateTime::createFromImmutable($dateTime) : $dateTime;
    }
}
