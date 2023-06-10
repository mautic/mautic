<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\ContentDTO;

final class AttachmentDTO implements \JsonSerializable
{
    public function __construct(private string $type, private string $name, private string $content)
    {
    }

    /** @return array<string, string> */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'data' => $this->content,
        ];
    }
}
