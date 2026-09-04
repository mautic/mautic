<?php

namespace Mautic\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    public const TABLE_NAME = 'email_stats';

    private ?int $id = null;

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

    private ?IpAddress $ipAddress = null;

    private ?\DateTimeInterface $dateSent = null;

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

    private ?\DateTimeInterface $lastOpened = null;

    /**
     * @var array
     */
    private $openDetails = [];

    /**
     * @var ArrayCollection|EmailReply[]
     */
    private $replies;

    /**
     * @var ArrayCollection|StatData[]
     */
    private $dataCollection;

    /**
     * @var ArrayCollection|StatOpenDetail[]
     */
    private $dataOpenDetails = [];

    /**
     * @var array<string,mixed[]>
     */
    private array $changes = [];

    public function __construct()
    {
        $this->replies         = new ArrayCollection();
        $this->dataCollection  = new ArrayCollection();
        $this->dataOpenDetails = new ArrayCollection();

        $data = new StatData();
        $data->setStat($this);
        $this->dataCollection->add($data);
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(StatRepository::class)
            ->addIndex(['email_id', 'lead_id'], 'stat_email_search')
            ->addIndex(['lead_id', 'email_id'], 'stat_email_search2')
            ->addIndex(['is_failed'], 'stat_email_failed_search')
            ->addIndex(['is_read', 'date_sent'], 'is_read_date_sent')
            ->addIndex(['tracking_hash'], 'stat_email_hash_search')
            ->addIndex(['source', 'source_id'], 'stat_email_source_search')
            ->addIndex(['date_sent'], 'email_date_sent')
            ->addIndex(['date_read', 'lead_id'], 'email_date_read_lead')
            ->addIndex(['lead_id', 'date_sent'], 'stat_email_lead_id_date_sent')
            ->addIndex(['email_id', 'is_read'], 'stat_email_email_id_is_read');

        $builder->addBigIntIdField();

        $builder->createManyToOne('email', 'Email')
            ->inversedBy('stats')
            ->addJoinColumn('email_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->addLead(true, 'SET NULL');

        $builder->createField('emailAddress', 'string')
            ->columnName('email_address')
            ->build();

        $builder->createManyToOne('list', LeadList::class)
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

        $builder->createManyToOne('storedCopy', Copy::class)
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

        // Mapped as OneToMany rather than OneToOne so it can lazy-load: an inverse-side to-one
        // can never be lazy, which would force it onto every Stat hydration in Mautic. The
        // shared-primary-key column on StatData still enforces at most one row per stat.
        $builder->createOneToMany('dataCollection', StatData::class)
            ->mappedBy('stat')
            ->cascadeAll()
            ->build();

        $builder->createOneToMany('dataOpenDetails', StatOpenDetail::class)
            ->orphanRemoval()
            ->mappedBy('stat')
            ->cascadeAll()
            ->fetchExtraLazy()
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
        $this->getData()->setDateSent($dateSent);
        // Denormalised onto each child row so MaintenanceSubscriber can compact
        // email_stats_open_details by date_sent without joining email_stats.
        foreach ($this->dataOpenDetails as $detail) {
            $detail->setDateSent($dateSent);
        }
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

    public function getId(): ?int
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
     * @return Collection<int,StatOpenDetail>
     */
    public function getDataOpenDetails(): Collection
    {
        return $this->dataOpenDetails;
    }

    public function getData(): StatData
    {
        if ($this->dataCollection->isEmpty()) {
            $data = new StatData();
            $data->setStat($this);
            $data->setDateSent($this->dateSent);
            $this->dataCollection->add($data);
        }

        return $this->dataCollection->first();
    }

    /**
     * @return array
     */
    public function getTokens()
    {
        // Maintain existing data from email_stats
        $tokens     = is_array($this->tokens) ? $this->tokens : [];
        $dataTokens = $this->dataCollection->isEmpty() ? null : $this->dataCollection->first()->getTokens();

        return array_merge($tokens, is_array($dataTokens) ? $dataTokens : []);
    }

    public function setTokens(array $tokens): void
    {
        // Migrate data to the new data table
        $this->tokens = [];
        $this->getData()->setTokens($tokens);
    }

    /**
     * @param array<string,mixed> $details
     */
    public function addOpenDetails(array $details, bool $increaseOpenCount = true): void
    {
        $storedCount = $increaseOpenCount ? $this->getOpenCount() : $this->dataOpenDetails->count();
        if (self::MAX_OPEN_DETAILS > $storedCount) {
            $entity = new StatOpenDetail();
            $entity->setStat($this);
            $entity->setDateSent($this->dateSent ?? new \DateTime());
            $entity->setOpenDetail($details);
            $this->dataOpenDetails->add($entity);
        }

        if ($increaseOpenCount) {
            ++$this->openCount;
        }
    }

    /**
     * @param array<string,mixed> $details
     */
    public function addBounceDetails(array $details): void
    {
        if (self::MAX_OPEN_DETAILS <= $this->dataOpenDetails->count()) {
            return;
        }

        $entity = new StatOpenDetail();
        $entity->setStat($this);
        $entity->setDateSent($this->dateSent ?? new \DateTime());
        $entity->setOpenDetail([StatOpenDetail::BOUNCES_KEY => [$details]]);
        $this->dataOpenDetails->add($entity);
    }

    /**
     * @return array
     */
    public function getOpenDetails()
    {
        // Maintain existing data from email_stats
        $openDetails = is_array($this->openDetails) ? $this->openDetails : [];
        foreach ($this->dataOpenDetails as $entity) {
            $openDetails = StatOpenDetail::mergeOpenDetail($openDetails, $entity->getOpenDetail(), $entity->getId());
        }

        return $openDetails;
    }

    /**
     * @param array<int|string,mixed> $openDetails
     *
     * @return Stat
     */
    public function setOpenDetails(array $openDetails)
    {
        $this->openDetails = [];

        [$keepPayloads, $toAdd] = $this->partitionOpenDetailsForSet($openDetails);

        // A row whose id is referenced in $openDetails is updated to match the (possibly edited)
        // content supplied for it; every other row is removed, whether or not it has been flushed
        // yet, so the result always matches $openDetails exactly.
        foreach ($this->dataOpenDetails as $entity) {
            $id = $entity->getId();
            if (isset($keepPayloads[$id])) {
                $entity->setOpenDetail($keepPayloads[$id]);
            } else {
                $this->dataOpenDetails->removeElement($entity);
            }
        }

        foreach ($toAdd as [$type, $detail]) {
            if ('bounce' === $type) {
                $this->addBounceDetails($detail);
            } else {
                $this->addOpenDetails($detail, false);
            }
        }

        return $this;
    }

    /**
     * Splits the incoming array into rows to keep (id => reconstructed stored payload) and rows
     * to insert as new (no id yet).
     *
     * @param array<int|string,mixed> $openDetails
     *
     * @return array{0: array<int,mixed>, 1: array<int,array{0: string, 1: array<string,mixed>}>}
     */
    private function partitionOpenDetailsForSet(array $openDetails): array
    {
        $keepPayloads = [];
        $toAdd        = [];

        foreach ($openDetails as $key => $detail) {
            if (StatOpenDetail::BOUNCES_KEY !== $key) {
                $this->collectOpenDetailEntry('open', $detail, $keepPayloads, $toAdd);
                continue;
            }

            foreach ($detail as $bounce) {
                $this->collectOpenDetailEntry('bounce', $bounce, $keepPayloads, $toAdd);
            }
        }

        return [$keepPayloads, $toAdd];
    }

    /**
     * @param array<string,mixed> $detail
     * @param array<mixed>        $keepPayloads
     * @param array<mixed>        $toAdd
     */
    private function collectOpenDetailEntry(string $type, array $detail, array &$keepPayloads, array &$toAdd): void
    {
        $id = $detail[StatOpenDetail::ROW_ID_KEY] ?? null;
        unset($detail[StatOpenDetail::ROW_ID_KEY]);

        if (null === $id) {
            $toAdd[] = [$type, $detail];

            return;
        }

        $keepPayloads[$id] = 'bounce' === $type ? [StatOpenDetail::BOUNCES_KEY => [$detail]] : $detail;
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
