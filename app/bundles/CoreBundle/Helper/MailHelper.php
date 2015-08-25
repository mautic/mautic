<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Swiftmailer\Exception\BatchQueueMaxedException;
use Mautic\CoreBundle\Swiftmailer\Exception\BatchQueueMaxException;
use Mautic\CoreBundle\Swiftmailer\Message\MauticMessage;
use Mautic\CoreBundle\Swiftmailer\Transport\InterfaceTokenTransport;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Helper\PlainTextHelper;
use Mautic\CoreBundle\Helper\EmojiHelper;

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
     * @var string
     */
    private $returnPath;

    /**
     * @var array
     */
    private $errors = array();

    /**
     * @var null
     */
    private $lead = null;

    /**
     * @var bool
     */
    private $internalSend = false;

    /**
     * @var null
     */
    private $idHash = null;

    /**
     * @var bool
     */
    private $appendTrackingPixel = false;

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
    private $globalTokens = array();

    /**
     * @var array
     */
    private $eventTokens   = array();

    /**
     * Tells the mailer to use batching/tokenized emails if it's available
     *
     * @var bool
     */
    private $tokenizationEnabled = false;

    /**
     * @var bool
     */
    private $tokenizationSupported = false;

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
    private $assets = array();

    /**
     * @var array
     */
    private $assetStats = array();

    /**
     * @var array
     */
    private $body = array(
        'content'     => '',
        'contentType' => 'text/html',
        'charset'     => null
    );

    /**
     * @var bool
     */
    private $fatal = false;

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

        $this->from       = (!empty($from)) ? $from : array($factory->getParameter('mailer_from_email') => $factory->getParameter('mailer_from_name'));
        $this->returnPath = $factory->getParameter('mailer_return_path');
        $this->message    = $this->getMessageInstance();

        // Check if batching is supported by the transport
        if ($this->factory->getParameter('mailer_spool_type') == 'memory' && $this->transport instanceof InterfaceTokenTransport) {
            $this->tokenizationSupported = true;
        }

        // Set factory if supported
        if (method_exists($this->transport, 'setMauticFactory')) {
            $this->transport->setMauticFactory($factory);
        }
    }

    /**
     * Send the message
     *
     * @param bool $dispatchSendEvent
     * @param bool $isQueueFlush
     *
     * @return bool
     */
    public function send($dispatchSendEvent = false, $isQueueFlush = false)
    {
        // Set from email
        $from = $this->message->getFrom();
        if (empty($from)) {
            $this->setFrom($this->from);
        }

        // Set system return path if applicable
        $returnPath = $this->message->getReturnPath();
        if (empty($returnPath) && !empty($this->returnPath)) {
            $this->message->setReturnPath($this->returnPath);
        }

        if (empty($this->errors)) {

            // Search/replace tokens if this is not a queue flush
            if (!$isQueueFlush) {
                // Generate tokens from listeners
                if ($dispatchSendEvent) {
                    $this->dispatchSendEvent();
                }

                // Queue an asset stat if applicable
                $this->queueAssetDownloadEntry();
            }

            $this->message->setSubject($this->subject);

            $this->message->setBody($this->body['content'], $this->body['contentType'], $this->body['charset']);

            if (!empty($this->plainText)) {
                $this->message->addPart($this->plainText, 'text/plain');
            }

            if (!$isQueueFlush) {
                // Replace token content
                $tokens = $this->getTokens();
                if (!empty($tokens)) {
                    // Replace tokens
                    $search  = array_keys($tokens);
                    $replace = $tokens;

                    self::searchReplaceTokens($search, $replace, $this->message);
                }
            }

            // Attach assets
            if (!empty($this->assets)) {
                /** @var \Mautic\AssetBundle\Entity\Asset $asset */
                foreach ($this->assets as $asset) {
                    $this->attachFile(
                        $asset->getFilePath(),
                        $asset->getOriginalFileName(),
                        $asset->getMime()
                    );
                }
            }

            try {
                $failures = array();
                $this->mailer->send($this->message, $failures);

                if (!empty($failures)) {
                    $this->errors['failures'] = $failures;

                    $this->logError('Sending failed for one or more recipients');
                }

                // Clear the log so that previous output is not associated with new errors
                $this->logger->clear();
            } catch (\Exception $e) {
                $this->logError($e);

                // Exception encountered when sending so all recipients are considered failures
                $this->errors['failures'] = array_merge(
                    array_keys((array) $this->message->getTo()),
                    array_keys((array) $this->message->getCc()),
                    array_keys((array) $this->message->getBcc())
                );
            }
        }

        $error = empty($this->errors);

        $this->createAssetDownloadEntries();

        return $error;
    }

    /**
     * If batching is supported and enabled, the message will be queued and will on be sent upon flushQueue().
     * Otherwise, the message will be sent to the transport immediately
     *
     * @param bool   $dispatchSendEvent
     * @param string $immediateSendMessageHandling If tokenization is not supported by the mailer, this argument determines
     *                                             what should happen to $this->message after the email send is attempted.
     *                                             Options are:
     *                                             RESET_TO           resets the to recipients and resets errors
     *                                             FULL_RESET         creates a new MauticMessage instance and resets errors
     *                                             DO_NOTHING         leaves the current errors array and MauticMessage instance intact
     *                                             NOTHING_IF_FAILED  leaves the current errors array MauticMessage instance intact if it fails, otherwise reset_to
     *
     *
     *
     * @return bool
     */
    public function queue($dispatchSendEvent = false, $immediateSendMessageHandling = 'RESET_TO')
    {
        if ($this->tokenizationEnabled) {

            // Dispatch event to get custom tokens from listeners
            if ($dispatchSendEvent) {
                $this->dispatchSendEvent();
            }

            // Metadata has to be set for each recipient
            foreach ($this->queuedRecipients as $email => $name) {
                $this->message->addMetadata($email,
                    array(
                        'leadId'   => (!empty($this->lead)) ? $this->lead['id'] : null,
                        'emailId'  => (!empty($this->email)) ? $this->email->getId() : null,
                        'hashId'   => $this->idHash,
                        'source'   => $this->source,
                        'tokens'   => $this->getTokens()
                    )
                );
            }

            // Add asset stats if applicable
            $this->queueAssetDownloadEntry();

            // Reset recipients
            $this->queuedRecipients = array();

            // Assume success
            return true;
        } else {
            $success = $this->send($dispatchSendEvent);

            // Reset the message for the next
            $this->queuedRecipients = array();

            // Reset message
            switch (ucwords($immediateSendMessageHandling)) {
                case 'RESET_TO':
                    $this->message->setTo(array());
                    $this->clearErrors();
                    break;
                case 'NOTHING_IF_FAILED':
                    if ($success) {
                        $this->message->setTo(array());
                        $this->clearErrors();
                    }

                    break;
                case 'FULL_RESET':
                    $this->message = $this->getMessageInstance();
                    $this->clearErrors();
                    break;
                case 'DO_NOTHING':
                default:
                    // Nada

                    break;
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
        if ($this->tokenizationEnabled) {
            $to = $this->message->getTo();
            if (!empty($to)) {
                $result = $this->send(false, true);

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

        // Batching was not enabled and thus sent with queue()
        return true;
    }


    /**
     * Reset's the mailer
     *
     * @param bool $cleanSlate
     */
    public function reset($cleanSlate = true)
    {
        unset($this->lead, $this->idHash, $this->eventTokens, $this->queuedRecipients, $this->errors);

        $this->eventTokens  = $this->queuedRecipients = $this->errors = array();
        $this->lead         = $this->idHash = null;
        $this->internalSend = $this->fatal = false;

        $this->logger->clear();

        if ($cleanSlate) {
            $this->appendTrackingPixel = false;

            unset($this->email, $this->source, $this->assets, $this->globalTokens, $this->message, $this->subject, $this->body, $this->plainText);

            $this->source  = $this->assets = $this->globalTokens = array();
            $this->email   = null;
            $this->subject = $this->plainText = '';
            $this->body    = array(
                'content'     => '',
                'contentType' => 'text/html',
                'charset'     => null
            );

            $this->tokenizationEnabled = false;

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
    static public function getPlainTextFromMessage(\Swift_Message $message)
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
     * @return string
     */
    static public function getBlankPixel()
    {
        return 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';
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
     * @param int|Asset $asset
     */
    public function attachAsset($asset)
    {
        $model = $this->factory->getModel('asset');

        if (!$asset instanceof Asset) {
            $asset = $model->getEntity($asset);

            if ($asset == null) {
                return;
            }
        }

        if ($asset->isPublished()) {
            $asset->setUploadDir($this->factory->getParameter('upload_dir'));
            $this->assets[$asset->getId()] = $asset;
        }
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
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
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
     * @return string
     */
    public function getPlainText()
    {
        return $this->plainText;
    }

    /**
     * @param        $content
     * @param string $contentType
     * @param null   $charset
     * @param bool   $ignoreTrackingPixel
     */
    public function setBody($content, $contentType = 'text/html', $charset = null, $ignoreTrackingPixel = false)
    {
        if (!$ignoreTrackingPixel) {
            // Append tracking pixel
            $trackingImg = '<img style="display: none;" height="1" width="1" src="{tracking_pixel}" />';
            if (strpos($content, '</body>') !== false) {
                $content = str_replace('</body>', $trackingImg.'</body>', $content);
            } else {
                $content .= $trackingImg;
            }
        }

        $this->body = array(
            'content'     => $content,
            'contentType' => $contentType,
            'charset'     => $charset
        );
    }

    /**
     * Get a copy of the raw body
     *
     * @return mixed
     */
    public function getBody()
    {
        return $this->body['content'];
    }

    /**
     * Set to address(es)
     *
     * @param $addresses
     * @param $name
     *
     * @return bool
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

            return true;
        } catch (\Exception $e) {
            $this->logError($e);

            return false;
        }
    }

    /**
     * Add to address
     *
     * @param      $address
     * @param null $name
     *
     * @return bool
     */
    public function addTo($address, $name = null)
    {
        $this->checkBatchMaxRecipients();

        try {
            $this->message->addTo($address, $name);
            $this->queuedRecipients[$address] = $name;

            return true;
        } catch (\Exception $e) {
            $this->logError($e);

            return false;
        }
    }

    /**
     * Set CC address(es)
     *
     * @param $addresses
     * @param $name
     *
     * @return bool
     */
    public function setCc($addresses, $name = null)
    {
        $this->checkBatchMaxRecipients(count($addresses), 'cc');

        try {
            $this->message->setCc($addresses, $name);

            return true;
        } catch (\Exception $e) {
            $this->logError($e);

            return false;
        }
    }

    /**
     * Add cc address
     *
     * @param      $address
     * @param null $name
     *
     * @return bool
     */
    public function addCc($address, $name = null)
    {
        $this->checkBatchMaxRecipients(1, 'cc');

        try {
            $this->message->addCc($address, $name);

            return true;
        } catch (\Exception $e) {
            $this->logError($e);

            return false;
        }
    }

    /**
     * Set BCC address(es)
     *
     * @param $addresses
     * @param $name
     *
     * @return bool
     */
    public function setBcc($addresses, $name = null)
    {
        $this->checkBatchMaxRecipients(count($addresses), 'bcc');

        try {
            $this->message->setBcc($addresses, $name);

            return true;
        } catch (\Exception $e) {
            $this->logError($e);

            return false;
        }
    }

    /**
     * Add bcc address
     *
     * @param      $address
     * @param null $name
     *
     * @return bool
     */
    public function addBcc($address, $name = null)
    {
        $this->checkBatchMaxRecipients(1, 'bcc');

        try {
            $this->message->addBcc($address, $name);

            return true;
        } catch (\Exception $e) {
            $this->logError($e);

            return false;
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
        if ($this->tokenizationEnabled) {
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
     * Set a custom return path
     *
     * @param $address
     */
    public function setReturnPath($address)
    {
        try {
            $this->message->setReturnPath($address);
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
     * Validates a given address to ensure RFC 2822, 3.6.2 specs
     *
     * @param $address
     *
     * @throws \Swift_RfcComplianceException
     */
    static public function validateEmail($address)
    {
        static $grammer;

        if ($grammer === null) {
            $grammer = new \Swift_Mime_Grammar();
        }

        if (!preg_match('/^'.$grammer->getDefinition('addr-spec').'$/D',
            $address)) {
            throw new \Swift_RfcComplianceException(
                'Address in mailbox given ['.$address.
                '] does not comply with RFC 2822, 3.6.2.'
            );
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
    public function setIdHash($idHash = null)
    {
        if ($idHash === null) {
            $idHash = uniqid();
        }

        $this->idHash = $idHash;

        // Append pixel to body before send
        $this->appendTrackingPixel = true;

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
     * @param bool internalSend  Set to true if the email is not being sent to this lead
     */
    public function setLead($lead, $interalSend = false)
    {
        $this->lead = $lead;

        $this->internalSend = $interalSend;
    }

    /**
     * Check if this is not being send directly to the lead
     *
     * @return bool
     */
    public function isInternalSend()
    {
        return $this->internalSend;
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
     * @param Email $email
     * @param bool  $allowBcc            Honor BCC if set in email
     * @param array $slots               Slots configured in theme
     * @param array $assetAttachments    Assets to send
     * @param bool  $ignoreTrackingPixel Do not append tracking pixel HTML
     */
    public function setEmail(Email $email, $allowBcc = true, $slots = array(), $assetAttachments = array(), $ignoreTrackingPixel = false)
    {
        $this->email = $email;

        $subject = $email->getSubject();

        // Convert short codes to emoji
        $subject = EmojiHelper::toEmoji($subject, 'short');

        // Set message settings from the email
        $this->setSubject($subject);

        $fromEmail = $email->getFromAddress();
        $fromName  = $email->getFromName();
        if (!empty($fromEmail) && !empty($fromEmail)) {
            $this->setFrom($fromEmail, $fromName);
        } else if (!empty($fromEmail)) {
            $this->setFrom($fromEmail, $this->from);
        } else if (!empty($fromName)) {
            $this->setFrom(key($this->from), $fromName);
        }

        $replyTo = $email->getReplyToAddress();
        if (!empty($replyTo)) {
            $this->setReplyTo($replyTo);
        }

        if ($allowBcc) {
            $bccAddress = $email->getBccAddress();
            if (!empty($bccAddress)) {
                $this->addBcc($bccAddress);
            }
        }

        if ($plainText = $email->getPlainText()) {
            $this->setPlainText($plainText);
        }

        $template = $email->getTemplate();
        if (!empty($template)) {
            if (empty($slots)) {
                $template = $email->getTemplate();
                $slots    = $this->factory->getTheme($template)->getSlots('email');
            }

            if (isset($slots[$template])) {
                $slots = $slots[$template];
            }

            $customHtml = $this->setTemplate('MauticEmailBundle::public.html.php', array(
                'slots'    => $slots,
                'content'  => $email->getContent(),
                'email'    => $email,
                'template' => $template
            ), true);
        } else {
            // Tak on the tracking pixel token
            $customHtml = $email->getCustomHtml();
        }

        // Convert short codes to emoji
        $customHtml = EmojiHelper::toEmoji($customHtml, 'short');

        $this->setBody($customHtml, 'text/html', null, $ignoreTrackingPixel);

        if (empty($assetAttachments)) {
            if ($assets = $email->getAssetAttachments()) {
                foreach ($assets as $asset) {
                    $this->attachAsset($asset);
                }
            }
        } else {
            foreach ($assetAttachments as $asset) {
                $this->attachAsset($asset);
            }
        }
    }

    /**
     * Append tokens
     *
     * @param array $tokens
     */
    public function addTokens(array $tokens)
    {
        $this->globalTokens = array_merge($this->globalTokens, $tokens);
    }

    /**
     * Set tokens
     *
     * @param array $tokens
     */
    public function setTokens(array $tokens)
    {
        $this->globalTokens = $tokens;
    }

    /**
     * Set custom tokens
     *
     * @param array $tokens
     *
     * @deprecated Since 1.1.  Use setTokens() instead. To be removed in 2.0
     */
    public function setCustomTokens(array $tokens)
    {
        $this->setTokens($tokens);
    }

    /**
     * Get tokens
     *
     * @return array
     */
    public function getTokens()
    {
        $tokens = array_merge($this->globalTokens, $this->eventTokens);

        // Include the tracking pixel token as it's auto appended to the body
        if ($this->appendTrackingPixel) {
            $tokens['{tracking_pixel}'] = $this->factory->getRouter()->generate(
                'mautic_email_tracker',
                array(
                    'idHash' => $this->idHash
                ),
                true
            );
        } else {
            $tokens['{tracking_pixel}'] = self::getBlankPixel();
        }

        return $tokens;
    }

    /**
     * Parses html into basic plaintext
     *
     * @param string $content
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
     * Tell the mailer to use batching/tokenized emails if available.  It's up to the function calling to execute flushQueue to send the mail.
     *
     * @param bool $tokenizationEnabled
     *
     * @return bool Returns true if batching/tokenization is supported by the mailer
     */
    public function useMailerTokenization($tokenizationEnabled = true)
    {
        if ($this->tokenizationSupported) {
            $this->tokenizationEnabled = $tokenizationEnabled;
        }

        return $this->tokenizationSupported;
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

        $event = new EmailSendEvent($this);

        $this->dispatcher->dispatch(EmailEvents::EMAIL_ON_SEND, $event);

        $this->eventTokens = array_merge($this->eventTokens, $event->getTokens());
    }

    /**
     * Log exception
     *
     * @param \Exception|string $error
     */
    private function logError($error)
    {
        if ($error instanceof \Exception) {
            $error = $error->getMessage();

            $this->fatal = true;
        }

        $logDump = $this->logger->dump();
        if (!empty($logDump) && strpos($error, $logDump) === false) {
            $error .= " Log data: $logDump";
        }

        $this->errors[] = $error;

        $this->logger->clear();

        $this->factory->getLogger()->log('error', '[MAIL ERROR] ' . $error);
    }

    /**
     * Get list of errors
     *
     * @param bool $reset Resets the error array in preparation for the next mail send or else it'll fail
     *
     * @return array
     */
    public function getErrors($reset = true)
    {
        $errors = $this->errors;

        if ($reset) {
            $this->clearErrors();
        }

        return $errors;
    }

    /**
     * Clears the errors from a previous send
     */
    public function clearErrors()
    {
        $this->errors = array();
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

    /**
     * Creates a download stat for the asset
     */
    private function createAssetDownloadEntries()
    {
        // Nothing was sent out so bail
        if ($this->fatal || empty($this->assetStats)) {
            return;
        }

        if (isset($this->errors['failures'])) {
            // Remove the failures from the asset queue
            foreach ($this->errors['failures'] as $failed) {
                unset($this->assetStats[$failed]);
            }
        }

        // Create a download entry if there is an Asset attachment
        if (!empty($this->assetStats)) {
            /** @var \Mautic\AssetBundle\Model\AssetModel $assetModel */
            $assetModel = $this->factory->getModel('asset');
            foreach ($this->assets as $asset) {
                foreach ($this->assetStats as $stat) {
                    $assetModel->trackDownload(
                        $asset,
                        null,
                        200,
                        $stat
                    );
                }
            }
        }

        // Reset the stat
        $this->assetStats = array();
    }

    /**
     * Queues the details to note if a lead received an asset if no errors are generated
     */
    private function queueAssetDownloadEntry()
    {
        if (!$this->internalSend && !empty($this->lead) && !empty($this->assets)) {
            $this->assetStats[$this->lead['email']] = array(
                'lead'        => $this->lead['id'],
                'email'       => $this->email->getId(),
                'source'      => array('email', $this->email->getId()),
                'tracking_id' => $this->idHash
            );
        }
    }

    /**
     * Returns if the mailer supports and is in tokenization mode
     *
     * @return bool
     */
    public function inTokenizationMode()
    {
        return $this->tokenizationEnabled;
    }

    /**
     * @param $url
     *
     * @return \Mautic\PageBundle\Entity\Redirect|null|object
     */
    public function getTrackableLink($url)
    {
        // Ensure a valid URL and that it has not already been found
        if (substr($url, 0, 4) !== 'http' && substr($url, 0, 3) !== 'ftp') {
            return null;
        }
        /** @var \Mautic\PageBundle\Model\RedirectModel $redirectModel */
        $redirectModel = $this->factory->getModel('page.redirect');

        $link = $redirectModel->getRedirectByUrl($url, $this->email);

        return $link;
    }

    /**
     * Create an email stat
     */
    public function createLeadEmailStat()
    {
        if (!$this->lead) {
            return;
        }

        //create a stat
        $stat = new Stat();
        $stat->setDateSent(new \DateTime());
        $stat->setEmail($this->email);
        $stat->setLead($this->factory->getEntityManager()->getReference('MauticLeadBundle:Lead', $this->lead['id']));

        $stat->setEmailAddress($this->lead['email']);
        $stat->setTrackingHash($this->idHash);
        if (!empty($this->source)) {
            $stat->setSource($this->source[0]);
            $stat->setSourceId($this->source[1]);
        }
        $stat->setCopy($this->getBody());
        $stat->setTokens($this->getTokens());

        /** @var \Mautic\EmailBundle\Model\EmailModel $emailModel */
        $emailModel = $this->factory->getModel('email');
        $emailModel->getStatRepository()->saveEntity($stat);
    }
}
