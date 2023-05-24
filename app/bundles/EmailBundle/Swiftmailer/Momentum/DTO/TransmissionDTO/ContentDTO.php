<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO;

use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\ContentDTO\AttachmentDTO;
use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\ContentDTO\FromDTO;

final class ContentDTO implements \JsonSerializable
{
    private string $subject;

    private FromDTO $from;

    private ?string $html = null;

    private ?string $inlineCss = null;

    private ?string $text = null;

    /** @var array<string, string> */
    private array $headers = [];

    /** @var AttachmentDTO[] */
    private array $attachments = [];

    public function __construct(string $subject, FromDTO $from)
    {
        $this->subject = $subject;
        $this->from    = $from;
    }

    public function setHtml(?string $html): self
    {
        $this->html = $html;

        return $this;
    }

    public function setInlineCss(?string $inlineCss = null): self
    {
        $this->inlineCss = $inlineCss;

        return $this;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function addHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;

        return $this;
    }

    public function addAttachment(AttachmentDTO $attachmentDTO): self
    {
        $this->attachments[] = $attachmentDTO;

        return $this;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $json = [
            'subject' => $this->subject,
            'from'    => $this->from,
        ];
        if (null !== $this->html) {
            $json['html'] = $this->html;
        }
        if (null !== $this->text) {
            $json['text'] = $this->text;
        }
        if (0 !== count($this->headers)) {
            $json['headers'] = $this->headers;
        }
        if (0 !== count($this->attachments)) {
            $json['attachments'] = $this->attachments;
        }
        if (null !== $this->inlineCss) {
            $json['inline_css'] = $this->inlineCss;
        }

        return $json;
    }
}
