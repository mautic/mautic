<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class Stat.
 */
class Stat
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var Email
     */
    private $email;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;

    /**
     * @var string
     */
    private $emailAddress;

    /**
     * @var \Mautic\LeadBundle\Entity\LeadList
     */
    private $list;

    /**
     * @var \Mautic\CoreBundle\Entity\IpAddress
     */
    private $ipAddress;

    /**
     * @var \DateTime
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
     * @var \DateTime
     */
    private $dateRead;

    /**
     * @var string
     */
    private $trackingHash;

    /**
     * @var int
     */
    private $retryCount = 0;

    /**
     * @var string
     */
    private $source;

    /**
     * @var int
     */
    private $sourceId;

    /**
     * @var array
     */
    private $tokens = [];

    /**
     * @var Copy
     */
    private $storedCopy;

    /**
     * @var int
     */
    private $openCount;

    /**
     * @var \DateTime
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

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('email_stats')
            ->setCustomRepositoryClass('Mautic\EmailBundle\Entity\StatRepository')
            ->addIndex(['email_id', 'lead_id'], 'stat_email_search')
            ->addIndex(['lead_id', 'email_id'], 'stat_email_search2')
            ->addIndex(['is_failed'], 'stat_email_failed_search')
            ->addIndex(['is_read'], 'stat_email_read_search')
            ->addIndex(['tracking_hash'], 'stat_email_hash_search')
            ->addIndex(['source', 'source_id'], 'stat_email_source_search')
            ->addIndex(['date_sent'], 'email_date_sent')
            ->addIndex(['date_read'], 'email_date_read');

        $builder->addId();

        $builder->createManyToOne('email', 'Email')
            ->inversedBy('stats')
            ->addJoinColumn('email_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->addLead(true, 'SET NULL');

        $builder->createField('emailAddress', 'string')
            ->columnName('email_address')
            ->build();

        $builder->createManyToOne('list', 'Mautic\LeadBundle\Entity\LeadList')
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

        $builder->createManyToOne('storedCopy', 'Mautic\EmailBundle\Entity\Copy')
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
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
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
     * @return mixed
     */
    public function getDateRead()
    {
        return $this->dateRead;
    }

    /**
     * @param mixed $dateRead
     */
    public function setDateRead($dateRead)
    {
        $this->dateRead = $dateRead;
    }

    /**
     * @return mixed
     */
    public function getDateSent()
    {
        return $this->dateSent;
    }

    /**
     * @param mixed $dateSent
     */
    public function setDateSent($dateSent)
    {
        $this->dateSent = $dateSent;
    }

    /**
     * @return Email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail(Email $email = null)
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return IpAddress
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * @param mixed $ip
     */
    public function setIpAddress(IpAddress $ip)
    {
        $this->ipAddress = $ip;
    }

    /**
     * @return mixed
     */
    public function getIsRead()
    {
        return $this->isRead;
    }

    /**
     * @return mixed
     */
    public function isRead()
    {
        return $this->getIsRead();
    }

    /**
     * @param mixed $isRead
     */
    public function setIsRead($isRead)
    {
        $this->isRead = $isRead;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param mixed $lead
     */
    public function setLead(Lead $lead = null)
    {
        $this->lead = $lead;
    }

    /**
     * @return mixed
     */
    public function getTrackingHash()
    {
        return $this->trackingHash;
    }

    /**
     * @param mixed $trackingHash
     */
    public function setTrackingHash($trackingHash)
    {
        $this->trackingHash = $trackingHash;
    }

    /**
     * @return \Mautic\LeadBundle\Entity\LeadList
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param mixed $list
     */
    public function setList($list)
    {
        $this->list = $list;
    }

    /**
     * @return mixed
     */
    public function getRetryCount()
    {
        return $this->retryCount;
    }

    /**
     * @param mixed $retryCount
     */
    public function setRetryCount($retryCount)
    {
        $this->retryCount = $retryCount;
    }

    /**
     * Increase the retry count.
     */
    public function upRetryCount()
    {
        ++$this->retryCount;
    }

    /**
     * @return mixed
     */
    public function getIsFailed()
    {
        return $this->isFailed;
    }

    /**
     * @param mixed $isFailed
     */
    public function setIsFailed($isFailed)
    {
        $this->isFailed = $isFailed;
    }

    /**
     * @return mixed
     */
    public function isFailed()
    {
        return $this->getIsFailed();
    }

    /**
     * @return mixed
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * @param mixed $emailAddress
     */
    public function setEmailAddress($emailAddress)
    {
        $this->emailAddress = $emailAddress;
    }

    /**
     * @return mixed
     */
    public function getViewedInBrowser()
    {
        return $this->viewedInBrowser;
    }

    /**
     * @param mixed $viewedInBrowser
     */
    public function setViewedInBrowser($viewedInBrowser)
    {
        $this->viewedInBrowser = $viewedInBrowser;
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param mixed $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @return mixed
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * @param mixed $sourceId
     */
    public function setSourceId($sourceId)
    {
        $this->sourceId = (int) $sourceId;
    }

    /**
     * @return mixed
     */
    public function getTokens()
    {
        return $this->tokens;
    }

    /**
     * @param mixed $tokens
     */
    public function setTokens($tokens)
    {
        $this->tokens = $tokens;
    }

    /**
     * @return mixed
     */
    public function getOpenCount()
    {
        return $this->openCount;
    }

    /**
     * @param mixed $openCount
     *
     * @return Stat
     */
    public function setOpenCount($openCount)
    {
        $this->openCount = $openCount;

        return $this;
    }

    /**
     * @param $details
     */
    public function addOpenDetails($details)
    {
        $this->openDetails[] = $details;

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
     * @return mixed
     */
    public function getLastOpened()
    {
        return $this->lastOpened;
    }

    /**
     * @param mixed $lastOpened
     *
     * @return Stat
     */
    public function setLastOpened($lastOpened)
    {
        $this->lastOpened = $lastOpened;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOpenDetails()
    {
        return $this->openDetails;
    }

    /**
     * @param mixed $openDetails
     *
     * @return Stat
     */
    public function setOpenDetails($openDetails)
    {
        $this->openDetails = $openDetails;

        return $this;
    }

    /**
     * @return Copy
     */
    public function getStoredCopy()
    {
        return $this->storedCopy;
    }

    /**
     * @param Copy $storedCopy
     *
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

    /**
     * @param EmailReply $reply
     */
    public function addReply(EmailReply $reply)
    {
        $this->replies[] = $reply;
    }
}
