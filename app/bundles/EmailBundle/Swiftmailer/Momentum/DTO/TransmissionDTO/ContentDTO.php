<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
    private $html = null;

    /**
     * @var string|null
     */
    private $inlineCss = null;

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
     * @var array
     */
    private $attachments = [];

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
     * @param AttachementDTO $attachementDTO
     *
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
        if (count($this->attachments) !== 0) {
            $json['attachments'] = $this->attachments;
        }
        if ($this->inlineCss !== null) {
            $json['inline_css'] = $this->inlineCss;
        }

        return $json;
    }
}
