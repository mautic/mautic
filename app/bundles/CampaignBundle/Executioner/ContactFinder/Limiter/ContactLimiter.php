<?php

namespace Mautic\CampaignBundle\Executioner\ContactFinder\Limiter;

use Mautic\CampaignBundle\Executioner\Exception\NoContactsFoundException;

class ContactLimiter
{
    private int $batchLimit;

    private ?int $contactId;

    private ?int $minContactId;

    private ?int $batchMinContactId = null;

    private ?int $maxContactId;

    private ?int $threadId = null;

    private ?int $maxThreads = null;

    /**
     * @var int|null
     */
    private $campaignLimit;

    private ?int $campaignLimitUsed = null;

    /**
     * @param int      $batchLimit
     * @param int|null $contactId
     * @param int|null $minContactId
     * @param int|null $maxContactId
     * @param int|null $threadId
     * @param int|null $maxThreads
     * @param int|null $campaignLimit
     */
    public function __construct(
        $batchLimit,
        $contactId = null,
        $minContactId = null,
        $maxContactId = null,
        private array $contactIdList = [],
        $threadId = null,
        $maxThreads = null,
        $campaignLimit = null
    ) {
        $this->batchLimit    = ($batchLimit) ? (int) $batchLimit : 100;
        $this->contactId     = ($contactId) ? (int) $contactId : null;
        $this->minContactId  = ($minContactId) ? (int) $minContactId : null;
        $this->maxContactId  = ($maxContactId) ? (int) $maxContactId : null;

        if ($threadId && $maxThreads) {
            $this->threadId     = (int) $threadId;
            $this->maxThreads   = (int) $maxThreads;

            if ($threadId > $maxThreads) {
                throw new \InvalidArgumentException('$threadId cannot be larger than $maxThreads');
            }
        }

        if ($campaignLimit) {
            $this->campaignLimit     = $campaignLimit;
            $this->campaignLimitUsed = 0;
        }
    }

    public function getBatchLimit(): int
    {
        return $this->batchLimit;
    }

    /**
     * @return int|null
     */
    public function getContactId()
    {
        return $this->contactId;
    }

    public function getMinContactId(): ?int
    {
        return $this->batchMinContactId ?: $this->minContactId;
    }

    /**
     * @return int|null
     */
    public function getMaxContactId()
    {
        return $this->maxContactId;
    }

    /**
     * @return array
     */
    public function getContactIdList()
    {
        return $this->contactIdList;
    }

    /**
     * @param int $id
     *
     * @return $this
     *
     * @throws NoContactsFoundException
     */
    public function setBatchMinContactId($id)
    {
        // Prevent a never ending loop if the contact ID never changes due to being the last batch of contacts
        if ($this->minContactId && $this->minContactId > (int) $id) {
            throw new NoContactsFoundException();
        }

        // We've surpasssed the max so bai
        if ($this->maxContactId && $this->maxContactId < (int) $id) {
            throw new NoContactsFoundException();
        }

        // The same batch of contacts were somehow processed so let's stop to prevent the loop
        if ($this->batchMinContactId && $this->batchMinContactId >= $id) {
            throw new NoContactsFoundException();
        }

        $this->batchMinContactId = (int) $id;

        return $this;
    }

    /**
     * @return $this
     */
    public function resetBatchMinContactId()
    {
        $this->batchMinContactId =  null;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getMaxThreads()
    {
        return $this->maxThreads;
    }

    /**
     * @return int|null
     */
    public function getThreadId()
    {
        return $this->threadId;
    }

    /**
     * @return int|null
     */
    public function getCampaignLimit()
    {
        return $this->campaignLimit;
    }

    public function hasCampaignLimit(): bool
    {
        return null !== $this->campaignLimit;
    }

    /**
     * @return int
     *
     * @throws \Exception
     */
    public function getCampaignLimitRemaining()
    {
        if (!$this->hasCampaignLimit()) {
            throw new \Exception('Campaign Limit was not set');
        }

        return $this->campaignLimit - $this->campaignLimitUsed;
    }

    /**
     * @return $this
     *
     * @throws \Exception
     */
    public function reduceCampaignLimitRemaining($reduction)
    {
        if (!$this->hasCampaignLimit()) {
            throw new \Exception('Campaign Limit was not set');
        } elseif ($this->campaignLimit < ($this->campaignLimitUsed + $reduction)) {
            throw new \Exception('Campaign Limit exceeded');
        }
        $this->campaignLimitUsed += $reduction;

        return $this;
    }

    /**
     * @return $this
     */
    public function resetCampaignLimitRemaining()
    {
        $this->campaignLimitUsed = 0;

        return $this;
    }
}
