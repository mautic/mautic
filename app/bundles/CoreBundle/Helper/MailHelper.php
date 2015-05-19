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
     * @var \Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine
     */
    private $templating = null;

    private $dispatcher = null;
    /**
     * @var \Swift_Plugins_Loggers_ArrayLogger
     */
    private $logger;

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
     * @var array
     */
    private $tokens = array();

    /**
     * @param MauticFactory $factory
     * @param               $mailer
     * @param null          $from
     */
    public function __construct(MauticFactory $factory, \Swift_Mailer $mailer, $from = null)
    {
        $this->factory    = $factory;
        $this->mailer     = $mailer;

        try {
            $this->logger = new \Swift_Plugins_Loggers_ArrayLogger();
            $this->mailer->registerPlugin(new \Swift_Plugins_LoggerPlugin($this->logger));
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }

        $this->from    = (!empty($from)) ? $from : array($factory->getParameter('mailer_from_email'), $factory->getParameter('mailer_from_name'));
        $this->message = $this->getMessageInstance();
    }

    /**
     * Reset's the mailer
     */
    public function reset()
    {
        unset($this->message, $this->lead, $this->email, $this->idHash, $this->errors, $this->token, $this->source);

        $this->errors = $this->tokens = $this->source = array();
        $this->lead   = $this->email  = $this->idHash = null;

        $this->logger->clear();

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
                if ($this->dispatcher == null) {
                    $this->dispatcher = $this->factory->getDispatcher();
                }
                $hasListeners = $this->dispatcher->hasListeners(EmailEvents::EMAIL_ON_SEND);
                if ($hasListeners) {
                    $content = $this->message->getBody();
                    $event   = new EmailSendEvent($content, $this->email, $this->lead, $this->idHash, $this->source, $this->tokens);
                    $this->dispatcher->dispatch(EmailEvents::EMAIL_ON_SEND, $event);
                    $content = $event->getContent(true);
                    $this->message->setBody($content);

                    unset($event, $content);
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
                    $this->factory->getLogger()->log('error', '[MAIL ERROR] ' . $this->logger->dump());
                }
            } catch (\Exception $e) {
                $this->errors[] = $e->getMessage();
                $this->factory->getLogger()->log('error', '[MAIL ERROR] ' . $this->logger->dump());
            }
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
        if ($this->templating == null) {
            $this->templating = $this->factory->getTemplating();
        }

        $content = $this->templating->renderResponse($template, $vars)->getContent();

        $this->message->setBody($content, 'text/html', $charset);

        unset($content, $vars);
    }

    /**
     * Set subject
     *
     * @param $subject
     */
    public function setSubject($subject)
    {
        $this->message->setSubject($subject);
    }

    /**
     * Set a plain text part
     *
     * @param $content
     */
    public function setPlainText($content)
    {
        $this->message->addPart($content, 'text/plain');
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
     * Set to address(es)
     *
     * @param $addresses
     * @param $name
     */
    public function setTo($addresses, $name = null)
    {
        try {
            $this->message->setTo($addresses, $name);
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            $this->factory->getLogger()->log('error', '[MAIL ERROR] ' . $e->getMessage());
        }
    }

    /**
     * Set CC address(es)
     *
     * @param $addresses
     * @param $name
     */
    public function setCc($addresses, $name = null)
    {
        try {
            $this->message->setCc($addresses, $name);
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            $this->factory->getLogger()->log('error', '[MAIL ERROR] ' . $e->getMessage());
        }
    }

    /**
     * Set BCC address(es)
     *
     * @param $addresses
     * @param $name
     */
    public function setBcc($addresses, $name = null)
    {
        try {
            $this->message->setBcc($addresses, $name);
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            $this->factory->getLogger()->log('error', '[MAIL ERROR] ' . $e->getMessage());
        }
    }

    /**
     * Set from address (defaults to system)
     *
     * @param $address
     * @param $name
     */
    public function setFrom($address, $name = null)
    {
        try {
            $this->message->setFrom($address, $name);
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            $this->factory->getLogger()->log('error', '[MAIL ERROR] ' . $e->getMessage());
        }
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

        // Add the trackingID to the $message object in order to update the stats if the email failed to send
        $this->message->leadIdHash = $idHash;
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

    /**
     * @param array $tokens
     */
    public function setCustomTokens(array $tokens)
    {
        $this->tokens = array_merge($this->tokens, $tokens);
    }

    /**
     * Parses html into basic plaintext
     */
    public function parsePlainText($content = null)
    {
        if ($content == null) {
            $content = $this->message->getBody();
        }

        // Remove line breaks (to prevent unwanted white space) then convert p and br to line breaks
        $search = array(
            "\n",
            "</p>",
            "<br />",
            "<br>"
        );

        $replace = array(
            '',
            "\n\n",
            "\n",
            "\n"
        );

        $content = str_ireplace($search, $replace, $content);

        // Strip tags
        $content = strip_tags($content);

        $this->message->addPart($content, 'text/plain');
    }
}
