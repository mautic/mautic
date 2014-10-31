<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;

class MailHelper
{

    private $factory;

    /**
     * @var
     */
    private $mailer;

    /**
     * @var
     */
    public  $message;

    /**
     * @var null
     */
    private $from;

    /*
     * @var null
     */
    private $failures;

    /**
     * @param      $mailer
     * @param null $from
     */
    public function __construct(MauticFactory $factory, $mailer, $from = null)
    {
        $this->factory = $factory;
        $this->mailer  = $mailer;
        $this->from    = $from;
        $this->message = $this->getMessageInstance();
    }

    /**
     * Get a Swift_Message instance
     */
    public function getMessageInstance()
    {
        return \Swift_Message::newInstance();
    }

    /**
     * Send the message
     *
     * @return bool
     */
    public function send()
    {
        $from = $this->message->getFrom();
        if (empty($from)) {
            $this->message->setFrom($this->from);
        }
        $this->mailer->send($this->message, $this->failures);

        if (empty($this->failures)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return mixed
     */
    public function getFailures()
    {
        return $this->failures;
    }

    /**
     * Add an attachment to email
     *
     * @param $filePath
     * @param $fileName
     * @param $contentType
     */
    public function attachFile($filePath, $fileName = null, $contentType = null, $inline = false)
    {
        $attachment = \Swift_Attachment::fromPath($filePath);

        if (!empty($fileName)) {
            $attachment->setFilename($fileName);
        }

        if (!empty($contentType)) {
            $attachment->setContentType($contentType);
        }

        if ($inline) {
            $attachment->setDisposition('inline');
        }

        $this->message->attach($attachment);
    }

    /**
     * Use a template as the body
     *
     * @param       $template
     * @param array $vars
     */
    public function setTemplate($template, $vars = array())
    {
        $content = $this->factory->getTemplating()->renderResponse($template, $vars)->getContent();
        $this->message->setBody($content, 'text/html');
    }
}