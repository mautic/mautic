<?php

namespace Mautic\EmailBundle\MonitoredEmail;

class Message
{
    public $id;

    public $date;

    public $subject;

    public $fromName;

    public $fromAddress;

    public $to = [];

    public $toString;

    public $cc         = [];

    public $replyTo    = [];

    public $inReplyTo  = false;

    public $returnPath = false;

    public $references = [];

    public string $textPlain = '';

    public $textHtml;

    public string $dsnReport  = '';

    public string $dsnMessage = '';

    public $fblReport;

    public $fblMessage;

    public $xHeaders = [];

    /**
     * @var Attachment[]
     */
    protected $attachments = [];

    public function addAttachment(Attachment $attachment): void
    {
        $this->attachments[$attachment->id] = $attachment;
    }

    /**
     * @return Attachment[]
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * Get array of internal HTML links placeholders.
     *
     * @return array attachmentId => link placeholder
     */
    public function getInternalLinksPlaceholders(): array
    {
        return preg_match_all('/=["\'](ci?d:([\w\.%*@-]+))["\']/i', $this->textHtml, $matches) ? array_combine($matches[2], $matches[1]) : [];
    }

    /**
     * @return mixed
     */
    public function replaceInternalLinks($baseUri)
    {
        $baseUri     = rtrim($baseUri, '\\/').'/';
        $fetchedHtml = $this->textHtml;
        foreach ($this->getInternalLinksPlaceholders() as $attachmentId => $placeholder) {
            if (isset($this->attachments[$attachmentId])) {
                $fetchedHtml = str_replace($placeholder, $baseUri.basename($this->attachments[$attachmentId]->filePath), $fetchedHtml);
            }
        }

        return $fetchedHtml;
    }
}
