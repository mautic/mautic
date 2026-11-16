<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\DTO\Repository;

final class EmailPendingQueryOptions
{
    /**
     * @var ?int[]
     */
    private ?array $variantIds = null;

    /**
     * @var ?int[]
     */
    private ?array $listIds = null;
    private bool $countOnly = false;
    private ?int $limit = null;
    private ?int $minContactId = null;
    private ?int $maxContactId = null;
    private bool $countWithMaxMin = false;
    private ?\DateTimeInterface $maxDate = null;
    private ?int $maxThreads = null;
    private ?int $threadId = null;
    private ?\DateTimeInterface $sendStopDate = null;
    private bool $segmentMembershipViaJoin = false;

    public function __construct(private readonly int $emailId)
    {
    }

    public function getEmailId(): int
    {
        return $this->emailId;
    }

    /**
     * @return ?int[]
     */
    public function getVariantIds(): ?array
    {
        return $this->variantIds;
    }

    /**
     * @param ?int[] $variantIds
     */
    public function setVariantIds(?array $variantIds): self
    {
        return $this->copy('variantIds', $variantIds);
    }

    /**
     * @return ?int[]
     */
    public function getListIds(): ?array
    {
        return $this->listIds;
    }

    /**
     * @param ?int[] $listIds
     */
    public function setListIds(?array $listIds): self
    {
        return $this->copy('listIds', $listIds);
    }

    public function isCountOnly(): bool
    {
        return $this->countOnly;
    }

    public function setCountOnly(bool $countOnly): self
    {
        return $this->copy('countOnly', $countOnly);
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setLimit(?int $limit): self
    {
        return $this->copy('limit', $limit);
    }

    public function getMinContactId(): ?int
    {
        return $this->minContactId;
    }

    public function setMinContactId(?int $minContactId): self
    {
        return $this->copy('minContactId', $minContactId);
    }

    public function getMaxContactId(): ?int
    {
        return $this->maxContactId;
    }

    public function setMaxContactId(?int $maxContactId): self
    {
        return $this->copy('maxContactId', $maxContactId);
    }

    public function isCountWithMaxMin(): bool
    {
        return $this->countWithMaxMin;
    }

    public function setCountWithMaxMin(bool $countWithMaxMin): self
    {
        return $this->copy('countWithMaxMin', $countWithMaxMin);
    }

    public function getMaxDate(): ?\DateTimeInterface
    {
        return $this->maxDate;
    }

    public function setMaxDate(?\DateTimeInterface $maxDate): self
    {
        return $this->copy('maxDate', $maxDate);
    }

    public function getMaxThreads(): ?int
    {
        return $this->maxThreads;
    }

    public function setMaxThreads(?int $maxThreads): self
    {
        return $this->copy('maxThreads', $maxThreads);
    }

    public function getThreadId(): ?int
    {
        return $this->threadId;
    }

    public function setThreadId(?int $threadId): self
    {
        return $this->copy('threadId', $threadId);
    }

    public function getSendStopDate(): ?\DateTimeInterface
    {
        return $this->sendStopDate;
    }

    public function setSendStopDate(?\DateTimeInterface $sendStopDate): self
    {
        return $this->copy('sendStopDate', $sendStopDate);
    }

    public function isSegmentMembershipViaJoin(): bool
    {
        return $this->segmentMembershipViaJoin;
    }

    public function setSegmentMembershipViaJoin(bool $segmentMembershipViaJoin): self
    {
        return $this->copy('segmentMembershipViaJoin', $segmentMembershipViaJoin);
    }

    private function copy(string $property, mixed $value): self
    {
        $copy            = clone $this;
        $copy->$property = $value;

        return $copy;
    }
}
