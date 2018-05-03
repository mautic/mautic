<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO;

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
    private $html = null;

    /**
     * @var string|null
     */
    private $text = null;

    /**
     * @var string|null
     */
    private $replyTo = null;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * ContentDTO constructor.
     *
     * @param         $subject
     * @param FromDTO $from
     */
    public function __construct($subject, FromDTO $from)
    {
        $this->subject = $subject;
        $this->from    = $from;
    }

    /**
     * @param null|string $html
     *
     * @return ContentDTO
     */
    public function setHtml($html)
    {
        $this->html = $html;

        return $this;
    }

    /**
     * @param null|string $text
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
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        $json = [
            'subject' => $this->subject,
            'from'    => $this->from,
        ];
        if ($this->html !== null) {
            $json['html'] = $this->html;
        }
        if ($this->text !== null) {
            $json['text'] = $this->text;
        }
        if ($this->replyTo !== null) {
            $json['reply_to'] = $this->replyTo;
        }
        if (count($this->headers) !== 0) {
            $json['headers'] = $this->headers;
        }

        return $json;
    }
}
