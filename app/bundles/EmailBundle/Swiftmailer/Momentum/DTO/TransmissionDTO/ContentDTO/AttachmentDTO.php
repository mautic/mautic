<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\ContentDTO;

final class AttachmentDTO implements \JsonSerializable
{
    private string $type;

    private string $name;

    private string $content;

    public function __construct(string $type, string $name, string $content)
    {
        $this->type    = $type;
        $this->name    = $name;
        $this->content = $content;
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
