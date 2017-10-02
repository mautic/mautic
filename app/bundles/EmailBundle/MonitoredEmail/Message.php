<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Modified from
 *
 * @see    https://github.com/barbushin/php-imap
 *
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 * @copyright BSD (three-clause)
 */

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

    public $textPlain;
    public $textHtml;
    public $dsnReport;
    public $dsnMessage;
    public $fblReport;
    public $fblMessage;
    public $xHeaders = [];

    /** @var Attachment[] */
    protected $attachments = [];

    /**
     * @param Attachment $attachment
     */
    public function addAttachment(Attachment $attachment)
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
    public function getInternalLinksPlaceholders()
    {
        return preg_match_all('/=["\'](ci?d:([\w\.%*@-]+))["\']/i', $this->textHtml, $matches) ? array_combine($matches[2], $matches[1]) : [];
    }

    /**
     * @param $baseUri
     *
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
