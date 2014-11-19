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

/**
 * Class MailHelper
 */
class MailHelper
{

    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * @var
     */
    private $mailer;

    /**
     * @var bool|\Swift_Message
     */
    public $message;

    /**
     * @var null
     */
    private $from;

    /**
     * @var array
     */
    private $errors = array();

    /**
     * @param MauticFactory $factory
     * @param               $mailer
     * @param null          $from
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
     *
     * @return bool|\Swift_Message
     */
    public function getMessageInstance()
    {
        try {
            $message = \Swift_Message::newInstance();

            return $message;
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();

            return false;
        }
    }

    /**
     * Send the message
     *
     * @return bool
     */
    public function send()
    {
        if (empty($this->errors)) {
            $from = $this->message->getFrom();
            if (empty($from)) {
                $this->message->setFrom($this->from);
            }

            try {
                $failures = array();
                $this->mailer->send($this->message, $failures);

                if (!empty($failures)) {
                    $this->errors['failures'] = $failures;
                }
            } catch (Exception $e) {
                $this->errors[] = $e->getMessage();
            }

            $this->mailer->send($this->message, $this->failures);
        }

        return empty($this->errors);
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Add an attachment to email
     *
     * @param string $filePath
     * @param string $fileName
     * @param string $contentType
     * @param bool   $inline
     *
     * @return void
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
     * @param string $template
     * @param array  $vars
     */
    public function setTemplate($template, $vars = array())
    {
        $content = $this->factory->getTemplating()->renderResponse($template, $vars)->getContent();
        $this->message->setBody($content, 'text/html');
    }
}
