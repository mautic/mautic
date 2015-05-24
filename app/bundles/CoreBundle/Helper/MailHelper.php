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
use Mautic\CoreBundle\Swiftmailer\Exception\BatchQueueMaxedException;
use Mautic\CoreBundle\Swiftmailer\Exception\BatchQueueMaxException;
use Mautic\CoreBundle\Swiftmailer\Message\MauticMessage;
use Mautic\CoreBundle\Swiftmailer\Transport\InterfaceBatchTransport;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Helper\PlainTextHelper;

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
     * @var
     */
    private $transport;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine
     */
    private $templating = null;

    /**
     * @var null
     */
    private $dispatcher = null;

    /**
     * @var \Swift_Plugins_Loggers_ArrayLogger
     */
    private $logger;

    /**
     * @var bool|MauticMessage
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
     * Tells the mailer to use batching if it's available
     *
     * @var bool
     */
    private $useBatching = false;

    /**
     * @var bool
     */
    private $batchingSupported = false;

    /**
     * @var array
     */
    private $queuedRecipients = array();

    /**
     * @var string
     */
    private $subject = '';

    /**
     * @var string
     */
    private $plainText = '';

    /**
     * @var array
     */
    private $body = array(
        'content'     => '',
        'contentType' => 'text/html',
        'charset'     => null
    );

    /**
     * @param MauticFactory $factory
     * @param               $mailer
     * @param null          $from
     */
    public function __construct(MauticFactory $factory, \Swift_Mailer $mailer, $from = null)
    {
        $this->factory   = $factory;
        $this->mailer    = $mailer;
        $this->transport = $mailer->getTransport();
        try {
            $this->logger = new \Swift_Plugins_Loggers_ArrayLogger();
            $this->mailer->registerPlugin(new \Swift_Plugins_LoggerPlugin($this->logger));
        } catch (\Exception $e) {
            $this->logError($e);
        }

        $this->from    = (!empty($from)) ? $from : array($factory->getParameter('mailer_from_email'), $factory->getParameter('mailer_from_name'));
        $this->message = $this->getMessageInstance();

        // Check if batching is supported by the transport
        if ($this->factory->getParameter('mailer_spool_type') == 'memory' && $this->transport instanceof InterfaceBatchTransport) {
            $this->batchingSupported = true;
        }
    }

    /**
     * Reset's the mailer
     *
     * @param bool $cleanSlate
     */
    public function reset($cleanSlate = true)
    {
        unset($this->lead, $this->email, $this->idHash, $this->tokens, $this->source, $this->queuedRecipients);

        $this->tokens = $this->source = $this->queuedRecipients = array();
        $this->lead   = $this->email  = $this->idHash = null;

        if ($cleanSlate) {
            unset($this->message, $this->subject, $this->body, $this->plainText);

            $this->subject = $this->plainText = '';
            $this->body    = array(
                'content'     => '',
                'contentType' => 'text/html',
                'charset'     => null
            );

            $this->errors = array();

            $this->logger->clear();

            $this->useBatching = false;

            $this->message = $this->getMessageInstance();
        }
    }

    /**
     * Search and replace tokens
     * Adapted from \Swift_Plugins_DecoratorPlugin
     *
     * @param array          $search
     * @param array          $replace
     * @param \Swift_Message $message
     */
    static function searchReplaceTokens($search, $replace, \Swift_Message &$message)
    {
        // Body
        $body         = $message->getBody();
        $bodyReplaced = str_ireplace($search, $replace, $body, $updated);
        if ($updated) {
            $message->setBody($bodyReplaced);
        }
        unset($body, $bodyReplaced);

        // Subject
        $subject      = $message->getSubject();
        $bodyReplaced = str_ireplace($search, $replace, $subject, $updated);

        if ($updated) {
            $message->setSubject($bodyReplaced);
        }
        unset($subject, $bodyReplaced);

        // Headers
        /** @var \Swift_Mime_Header $header */
        foreach ($message->getHeaders()->getAll() as $header) {
            $headerBody = $header->getFieldBodyModel();
            $updated    = false;
            if (is_array($headerBody)) {
                $bodyReplaced = array();
                foreach ($headerBody as $key => $value) {
                    $count1             = $count2 = 0;
                    $key                = is_string($key) ? str_ireplace($search, $replace, $key, $count1) : $key;
                    $value              = is_string($value) ? str_ireplace($search, $replace, $value, $count2) : $value;
                    $bodyReplaced[$key] = $value;
                    if (($count1 + $count2)) {
                        $updated = true;
                    }
                }
            } else {
                $bodyReplaced = str_ireplace($search, $replace, $headerBody, $updated);
            }

            if (!empty($updated)) {
                $header->setFieldBodyModel($bodyReplaced);
            }

            unset($headerBody, $bodyReplaced);
        }

        // Parts (plaintext)
        $children = (array) $message->getChildren();
        /** @var \Swift_Mime_MimeEntity $child */
        foreach ($children as $child) {
            $childType    = $child->getContentType();
            list($type, ) = sscanf($childType, '%[^/]/%s');

            if ($type == 'text') {
                $childBody    = $child->getBody();

                $bodyReplaced = str_ireplace($search, $replace, $childBody);
                if ($childBody != $bodyReplaced) {
                    $child->setBody($bodyReplaced);
                    $childBody = $bodyReplaced;
                }
            }

            unset($childBody, $bodyReplaced);
        }
    }

    /**
     * Extract plain text from message
     *
     * @param \Swift_Message $message
     *
     * @return string
     */
    static public function getPlainText(\Swift_Message $message)
    {
        $children = (array) $message->getChildren();

        /** @var \Swift_Mime_MimeEntity $child */
        foreach ($children as $child) {
            $childType = $child->getContentType();
            if ($childType == 'text/plain' && $child instanceof \Swift_MimePart) {
                return $child->getBody();
            }
        }

        return '';
    }

    /**
     * Get a MauticMessage/Swift_Message instance
     *
     * @return bool|MauticMessage
     */
    public function getMessageInstance()
    {
        try {
            $message = MauticMessage::newInstance();

            return $message;
        } catch (\Exception $e) {
            $this->logError($e);

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
        // Set from email
        $from = $this->message->getFrom();
        if (empty($from)) {
            $this->setFrom($this->from);
        }

        if (empty($this->errors)) {
            $this->message->setSubject($this->subject);

            $this->message->setBody($this->body['content'], $this->body['contentType'], $this->body['charset']);

            if (!empty($this->plainText)) {
                $this->message->addPart($this->plainText, 'text/plain');
            }

            $tokens = ($dispatchSendEvent) ? $this->dispatchSendEvent() : false;

            if (!empty($tokens)) {
                // Replace tokens
                $search  = array_keys($tokens);
                $replace = $tokens;

                self::searchReplaceTokens($search, $replace, $this->message);
            }

            try {
                $failures = array();
                $this->mailer->send($this->message, $failures);

                if (!empty($failures)) {
                    $this->errors['failures'] = $failures;
                    $this->factory->getLogger()->log('error', '[MAIL ERROR] '.$this->logger->dump());
                }
            } catch (\Exception $e) {
                $this->logError($e);
            }
        }

        return empty($this->errors);
    }

    /**
     * If batching is supported and enabled, the message will be queued and will on be sent upon flushQueue().
     * Otherwise, the message will be sent to the mailer immediately
     *
     * @param bool $dispatchSendEvent
     * @param bool $resetMessageIfNotQueued If the email is sent immediately due to the mailer not supporting batching, reset message
     *
     * @return bool
     */
    public function queue($dispatchSendEvent = false, $resetMessageIfNotQueued = true)
    {
        if ($this->useBatching) {
            // Metadata has to be set for each recipient
            foreach ($this->queuedRecipients as $email => $name) {
                $this->message->addMetadata($email,
                    array(
                        'leadId'   => (!empty($this->lead)) ? $this->lead['id'] : null,
                        'emailId'  => (!empty($this->email)) ? $this->email->getId() : null,
                        'hashId'   => $this->idHash,
                        'source'   => $this->source,
                        'tokens'   => ($dispatchSendEvent) ? $this->dispatchSendEvent() : array()
                    )
                );
            }

            $this->queuedRecipients = array();

            // Assume success
            return true;
        } else {
            $success = $this->send($dispatchSendEvent);

            if ($resetMessageIfNotQueued) {
                unset($this->message);
                $this->message = $this->getMessageInstance();
            }

            return $success;
        }
    }

    /**
     * Send batched mail to mailer
     *
     * @param array $resetEmailTypes Array of email types to clear after flusing the queue
     *
     * @return bool
     */
    public function flushQueue($resetEmailTypes = array('To', 'Cc', 'Bcc'))
    {
        $to = $this->message->getTo();
        if (!empty($to)) {
            $result = $this->send(false);

            // Clear queued to recipients
            $this->queuedRecipients = array();

            foreach ($resetEmailTypes as $type) {
                $type    = ucfirst($type);
                $headers = $this->message->getHeaders();

                if ($headers->has($type)) {
                    $this->message->getHeaders()->remove($type);
                }
            }

            // Clear metadat for the previous recipients
            $this->message->clearMetadata();

            return $result;
        }

        return false;
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
     * @param bool   $returnContent
     * @param null   $charset
     *
     * @return void
     */
    public function setTemplate($template, $vars = array(), $returnContent = false, $charset = null)
    {
        if ($this->templating == null) {
            $this->templating = $this->factory->getTemplating();
        }

        $content = $this->templating->renderResponse($template, $vars)->getContent();

        unset($vars);

        if ($returnContent) {
            return $content;
        }

        $this->setBody($content, 'text/html', $charset);
        unset($content);
    }

    /**
     * Set subject
     *
     * @param $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * Set a plain text part
     *
     * @param $content
     */
    public function setPlainText($content)
    {
        $this->plainText = $content;
    }

    /**
     * @param        $content
     * @param string $contentType
     * @param null   $charset
     */
    public function setBody($content, $contentType = 'text/html', $charset = null)
    {
        $this->body = array(
            'content'     => $content,
            'contentType' => $contentType,
            'charset'     => $charset
        );
    }

    /**
     * Set to address(es)
     *
     * @param $addresses
     * @param $name
     */
    public function setTo($addresses, $name = null)
    {
        if (!is_array($addresses)) {
            $addresses = array($addresses => $name);
        }

        $this->checkBatchMaxRecipients(count($addresses));

        try {
            $this->message->setTo($addresses);
            $this->queuedRecipients = array_merge($this->queuedRecipients, $addresses);
        } catch (\Exception $e) {
            $this->logError($e);
        }
    }

    /**
     * Add to address
     *
     * @param      $address
     * @param null $name
     */
    public function addTo($address, $name = null)
    {
        $this->checkBatchMaxRecipients();

        try {
            $this->message->addTo($address, $name);
            $this->queuedRecipients[$address] = $name;
        } catch (\Exception $e) {
            $this->logError($e);
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
        $this->checkBatchMaxRecipients(count($addresses), 'cc');

        try {
            $this->message->setCc($addresses, $name);
        } catch (\Exception $e) {
            $this->logError($e);
        }
    }

    /**
     * Add cc address
     *
     * @param      $address
     * @param null $name
     */
    public function addCc($address, $name = null)
    {
        $this->checkBatchMaxRecipients(1, 'cc');

        try {
            $this->message->addCc($address, $name);
        } catch (\Exception $e) {
            $this->logError($e);
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
        $this->checkBatchMaxRecipients(count($addresses), 'bcc');

        try {
            $this->message->setBcc($addresses, $name);
        } catch (\Exception $e) {
            $this->logError($e);
        }
    }

    /**
     * Add bcc address
     *
     * @param      $address
     * @param null $name
     */
    public function addBcc($address, $name = null)
    {
        $this->checkBatchMaxRecipients(1, 'bcc');

        try {
            $this->message->addBcc($address, $name);
        } catch (\Exception $e) {
            $this->logError($e);
        }
    }

    /**
     * @param int    $toBeAdded
     * @param string $type
     *
     * @throws BatchQueueMaxException
     */
    private function checkBatchMaxRecipients($toBeAdded = 1, $type = 'to')
    {
        if ($this->useBatching) {
            // Check if max batching has been hit
            $maxAllowed = $this->transport->getMaxBatchLimit();

            if ($maxAllowed > 0) {
                $currentCount = $this->transport->getBatchRecipientCount($this->message, $toBeAdded, $type);

                if ($currentCount > $maxAllowed) {
                    throw new BatchQueueMaxException();
                }
            }
        }
    }

    /**
     * Set reply to address(es)
     *
     * @param $addresses
     * @param $name
     */
    public function setReplyTo($addresses, $name = null)
    {
        try {
            $this->message->setReplyTo($addresses, $name);
        } catch (\Exception $e) {
            $this->logError($e);
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
            $this->logError($e);
        }
    }

    /**
     * @return null
     */
    public function getIdHash()
    {
        return $this->idHash;
    }

    /**
     * @param null $idHash
     */
    public function setIdHash($idHash)
    {
        $this->idHash = $idHash;

        // Add the trackingID to the $message object in order to update the stats if the email failed to send
        $this->message->leadIdHash = $idHash;
    }

    /**
     * @return null
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param null $lead
     */
    public function setLead($lead)
    {
        $this->lead = $lead;
    }

    /**
     * @return array
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param array $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @return null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param null $email
     */
    public function setEmail($email)
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

        $request = $this->factory->getRequest();
        $parser  = new PlainTextHelper(array(
            'base_url' => $request->getSchemeAndHttpHost() . $request->getBasePath()
        ));

        $this->plainText = $parser->setHtml($content)->getText();
    }

    /**
     * Tell the mailer to use batching if available.  It's up to the function calling to execute the batch send.
     *
     * @param bool $useBatching
     *
     * @return bool Returns true if batching is supported by the mailer
     */
    public function useMailerBatching($useBatching = true)
    {

        if ($this->batchingSupported) {
            $this->useBatching = $useBatching;
        }

        return $this->batchingSupported;
    }

    /**
     * Dispatch send event to generate tokens
     *
     * @return array
     */
    public function dispatchSendEvent()
    {
        if ($this->dispatcher == null) {
            $this->dispatcher = $this->factory->getDispatcher();
        }

        $event = new EmailSendEvent($this->body['content'], $this->email, $this->lead, $this->idHash, $this->source, $this->tokens, $this->useBatching);

        $this->dispatcher->dispatch(EmailEvents::EMAIL_ON_SEND, $event);

        $tokens = $event->getTokens();

        unset($event);

        return $tokens;
    }

    /**
     * Log exception
     *
     * @param \Exception|string $error
     */
    private function logError($error)
    {
        $error = ($error instanceof \Exception) ? $error->getMessage() : $error;

        $this->errors[] = $error;

        $logDump = $this->logger->dump();

        if (!empty($logDump)) {
            $error .= "; $logDump";
            $this->logger->clear();
        }

        $this->factory->getLogger()->log('error', '[MAIL ERROR] ' . $error);
    }

    /**
     * Return transport
     *
     * @return \Swift_Transport
     */
    public function getTransport()
    {
        return $this->transport;
    }
}
