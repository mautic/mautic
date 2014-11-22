<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;

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
     * @var null
     */
    private $lead = null;

    /**
     * @var null
     */
    private $idHash = null;

    /**
     * @var array
     */
    private $source = array();

    /**
     * @var null
     */
    private $email = null;


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
     * @param bool $dispatchSendEvent
     *
     * @return bool
     */
    public function send($dispatchSendEvent = false)
    {
        if (empty($this->errors)) {
            if ($dispatchSendEvent) {
                $dispatcher   = $this->factory->getDispatcher();
                $hasListeners = $dispatcher->hasListeners(EmailEvents::EMAIL_ON_SEND);
                if ($hasListeners) {
                    $content = $this->message->getBody();
                    $event   = new EmailSendEvent($content, $this->email, $this->lead, $this->idHash, $this->source);
                    $dispatcher->dispatch(EmailEvents::EMAIL_ON_SEND, $event);
                    $content = $event->getContent();
                    $this->message->setBody($content);
                }
            }

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
     * @param bool   $replaceTokens
     * @param null   $charset
     */
    public function setTemplate($template, $vars = array(), $charset = null)
    {
        $content = $this->factory->getTemplating()->renderResponse($template, $vars)->getContent();

        $this->message->setBody($content, 'text/html', $charset);
    }

    /**
     * @param        $content
     * @param string $contentType
     * @param null   $charset
     */
    public function setBody($content, $contentType = 'text/html', $charset = null)
    {
        $this->message->setBody($content, $contentType, $charset);
    }

    /**
     * @return null
     */
    public function getIdHash ()
    {
        return $this->idHash;
    }

    /**
     * @param null $idHash
     */
    public function setIdHash ($idHash)
    {
        $this->idHash = $idHash;
    }

    /**
     * @return null
     */
    public function getLead ()
    {
        return $this->lead;
    }

    /**
     * @param null $lead
     */
    public function setLead ($lead)
    {
        $this->lead = $lead;
    }

    /**
     * @return array
     */
    public function getSource ()
    {
        return $this->source;
    }

    /**
     * @param array $source
     */
    public function setSource ($source)
    {
        $this->source = $source;
    }

    /**
     * @return null
     */
    public function getEmail ()
    {
        return $this->email;
    }

    /**
     * @param null $email
     */
    public function setEmail ($email)
    {
        $this->email = $email;
    }
}
