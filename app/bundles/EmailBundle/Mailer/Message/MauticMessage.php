<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
     * Create a new Message.
     *
     * @param string $subject
     * @param string $body
     */
    public static function newInstance(?string $subject = null, ?string $body = null): MauticMessage
    {
        return (new self())
            ->subject($subject)
            ->html($body);
    }

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
}
