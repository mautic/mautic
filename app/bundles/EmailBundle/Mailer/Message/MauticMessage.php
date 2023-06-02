<?php

namespace Mautic\EmailBundle\Mailer\Message;

use Symfony\Component\Mime\Email;

class MauticMessage extends Email
{
    /**
     * @var array<string, array<string, string>>
     */
    protected $metadata = [];

    protected ?string $leadIdHash;

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

    /**
     * @param string     $filePath
     * @param null       $fileName
     * @param null       $contentType
     * @param bool|false $inline
     */
    public function addAttachment($filePath, $fileName = null, $contentType = null, $inline = false): void
    {
        if (true === $inline) {
            $this->embedFromPath($filePath, $fileName, $contentType);

            return;
        }

        $this->attachFromPath($filePath, $fileName, $contentType);
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
