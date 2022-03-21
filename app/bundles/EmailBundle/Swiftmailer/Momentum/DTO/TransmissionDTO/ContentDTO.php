<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO;

use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\ContentDTO\AttachementDTO;
use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\ContentDTO\FromDTO;

/**
 * Class ContentDTO.
 */
final class ContentDTO implements \JsonSerializable
{
    /**
     * @var string
     */
    private $subject;

    /**
     * @var FromDTO
     */
    private $from = [];

    /**
     * @var string|null
     */
    private $html;

    /**
     * @var string|null
     */
    private $inlineCss;

    /**
     * @var string|null
     */
    private $text;

    /**
     * @var string|null
     */
    private $replyTo;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var array
     */
    private $attachments = [];

    /**
     * ContentDTO constructor.
     *
     * @param $subject
     */
    public function __construct($subject, FromDTO $from)
    {
        $this->subject = $subject;
        $this->from    = $from;
    }

    /**
     * @param string|null $html
     *
     * @return ContentDTO
     */
    public function setHtml($html)
    {
        $this->html = $html;

        return $this;
    }

    /**
     * @param string|null $inlineCss
     *
     * @return ContentDTO
     */
    public function setInlineCss($inlineCss = null)
    {
        $this->inlineCss = $inlineCss;

        return $this;
    }

    /**
     * @param string|null $text
     *
     * @return ContentDTO
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return ContentDTO
     */
    public function addHeader($key, $value)
    {
        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * @return $this
     */
    public function addAttachment(AttachementDTO $attachementDTO)
    {
        $this->attachments[] = $attachementDTO;

        return $this;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
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
        if (null !== $this->replyTo) {
            $json['reply_to'] = $this->replyTo;
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
