<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Mailer\Message;

use Symfony\Component\Mime\Email;

class MauticMessage extends Email
{
    /**
     * @var array<string, array<string, string>>
     */
    protected $metadata = [];

    protected ?string $leadIdHash = null;

    /**
     * @param array<string, string> $metadata
     */
    public function addMetadata(string $email, array $metadata): void
    {
        $this->metadata[$email] = $metadata;
    }

    /**
     * Get the metadata.
     *
     * @return array<string, array<string, string>>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Clears the metadata.
     */
    public function clearMetadata(): void
    {
        $this->metadata = [];
    }

    public function updateLeadIdHash(?string $hash): void
    {
        $this->leadIdHash = $hash;
    }

    public function getLeadIdHash(): ?string
    {
        return $this->leadIdHash;
    }

    /**
     * @return array<mixed>
     */
    public function __serialize(): array
    {
        if (empty($this->leadIdHash)) {
            $this->leadIdHash = '';
        }

        return [$this->metadata, $this->leadIdHash, parent::__serialize()];
    }

    /**
     * @param array<mixed> $data
     */
    public function __unserialize(array $data): void
    {
        [$this->metadata, $this->leadIdHash, $parentData] = $data;

        parent::__unserialize($parentData);
    }
}
