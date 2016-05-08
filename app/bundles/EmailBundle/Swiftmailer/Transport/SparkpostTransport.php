<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @see         https://github.com/SlowProg/SparkPostSwiftMailer/blob/master/SwiftMailer/SparkPostTransport.php for additional source reference
 */

namespace Mautic\EmailBundle\Swiftmailer\Transport;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\EmailBundle\Helper\MailHelper;
use Symfony\Component\HttpFoundation\Request;

use SparkPost\SparkPost;
use GuzzleHttp\Client;
use Ivory\HttpAdapter\Guzzle6HttpAdapter;

use \Swift_Events_EventDispatcher;
use \Swift_Events_EventListener;
use \Swift_Events_SendEvent;
use \Swift_Mime_Message;
use \Swift_Transport;
use \Swift_Attachment;
use \Swift_MimePart;

/**
 * Class SparkpostTransport
 * The referrence class for this was provided by
 * 
 */
class SparkpostTransport implements \Swift_Transport
{
    /** 
     * @var \Swift_Events_SimpleEventDispatcher|null
     */
    protected $dispatcher;

    /**
     * @var string|null
     */
    protected $apiKey;

    /** 
     * @var array|null
     */
    protected $apiResponsePayload;

    /** 
      * @var array|null
      */
    protected $fromEmail;

    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * @var         $this->factory->getLogger();
     */
    private $logger;

    public function __construct($apiKey)
    {
        $this->setApiKey($apiKey);
        $this->getDispatcher();
    }

    /**
     * TODO: To DRY this up, this may need to be abstracted out to some kind of AbstractApiKeyHttpTransport
     * @return \Swift_Events_SimpleEventDispatcher
     */
    protected function getDispatcher()
    {
        if ($this->dispatcher == null) {
            $this->dispatcher = new \Swift_Events_SimpleEventDispatcher();
        }
        return $this->dispatcher;
    }

    /**
     * TODO: To DRY this up, this may need to be abstracted out to some kind of AbstractApiKeyHttpTransport
     * @param MauticFactory $factory
     */
    public function setMauticFactory(MauticFactory $factory)
    {
        $this->factory = $factory;
        $this->setLogger();
        $this->logger->error("Here is the apikey $this->apiKey");
    }
    
    // TODO: Remove this when you're done building this transport!
    public function setLogger()
    {
        $this->logger = $this->factory->getLogger();
    }
    
    /**
     * Not used
     */
    public function setUsername($username)
    {

    }

    /**
     * Not used
     */
    public function getUsername()
    {

    }

    /**
     * Not used
     */
    public function setPassword($password)
    {

    }

    /**
     * Not used
     */
    public function isStarted()
    {
        return false;
    }

    /**
     * Not used
     */
    public function start()
    {
    }

    /**
     * Not used
     */
    public function stop()
    {
    }

    /**
     * @param string $apiKey
     * @return $this
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
        //return $this;
    }
    /**
     * @return null|string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }
    /**
     * @return SparkPost\SparkPost
     * @throws \Swift_TransportException
     */
    protected function createSparkPost()
    {
        if ($this->apiKey === null)
            throw new \Swift_TransportException('Cannot create instance of \SparkPost\SparkPost while API key is NULL');
        return new SparkPost(
            new Guzzle6HttpAdapter(new Client()),
            ['key' => $this->apiKey]
        );
    }
    /**
     * @param Swift_Mime_Message $message
     * @param null $failedRecipients
     * @return int Number of messages sent
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $this->apiResponsePayload = null;
        if ($event = $this->dispatcher->createSendEvent($this, $message)) {
            $this->dispatcher->dispatchEvent($event, 'beforeSendPerformed');
            if ($event->bubbleCancelled()) {
                return 0;
            }
        }
        $sendCount                = 0;
        $sparkPost                = $this->createSparkPost();
        $sparkPostMessage         = $this->getSparkPostMessage($message);
        $this->apiResponsePayload = $sparkPost->transmission->send($sparkPostMessage);
        $sendCount                = $this->apiResponsePayload['results']['total_accepted_recipients'];
        
        if ($this->apiResponsePayload['results']['total_rejected_recipients'] > 0) {
            $failedRecipients[] = $this->fromEmail;
        }
        if ($event) {
            if ($sendCount > 0) {
                $event->setResult(Swift_Events_SendEvent::RESULT_SUCCESS);
            } else {
                $event->setResult(Swift_Events_SendEvent::RESULT_FAILED);
            }
            $this->dispatcher->dispatchEvent($event, 'sendPerformed');
        }
        return $sendCount;
    }
    /**
     * @param Swift_Events_EventListener $plugin
     */
    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
        $this->dispatcher->bindEventListener($plugin);
    }
    /**
     * @return array
     */
    protected function getSupportedContentTypes()
    {
        return array(
            'text/plain',
            'text/html'
        );
    }
    /**
     * @param string $contentType
     * @return bool
     */
    protected function supportsContentType($contentType)
    {
        return in_array($contentType, $this->getSupportedContentTypes());
    }
    /**
     * @param Swift_Mime_Message $message
     * @return string
     */
    protected function getMessagePrimaryContentType(Swift_Mime_Message $message)
    {
        $contentType = $message->getContentType();
        if($this->supportsContentType($contentType)){
            return $contentType;
        }
        // SwiftMailer hides the content type set in the constructor of Swift_Mime_Message as soon
        // as you add another part to the message. We need to access the protected property
        // _userContentType to get the original type.
        $messageRef = new \ReflectionClass($message);
        if($messageRef->hasProperty('_userContentType')){
            $propRef = $messageRef->getProperty('_userContentType');
            $propRef->setAccessible(true);
            $contentType = $propRef->getValue($message);
        }
        return $contentType;
    }
    /**
     * https://jsapi.apiary.io/apis/sparkpostapi/introduction/subaccounts-coming-to-an-api-near-you-in-april!.html
     *
     * @param Swift_Mime_Message $message
     * @return array SparkPost Send Message
     * @throws \Swift_SwiftException
     */
    public function getSparkPostMessage(Swift_Mime_Message $message)
    {
        $contentType = $this->getMessagePrimaryContentType($message);
        $fromAddresses = $message->getFrom();
        $fromEmails = array_keys($fromAddresses);
        list($fromFirstEmail, $fromFirstName) = each($fromAddresses);
        $this->fromEmail = $fromFirstEmail;
        $from = $fromFirstName?$fromFirstName.' <'.$fromFirstEmail.'>':$fromFirstEmail;
        $toAddresses = $message->getTo();
        $ccAddresses = $message->getCc() ? $message->getCc() : [];
        $bccAddresses = $message->getBcc() ? $message->getBcc() : [];
        $replyToAddresses = $message->getReplyTo() ? $message->getReplyTo() : [];
        $recipients = array();
        $cc = array();
        $bcc = array();
        $attachments = array();
        $headers = array();
        $tags = array();
        $inlineCss = null;
        foreach ($toAddresses as $toEmail => $toName) {
            $recipients[] = array(
                'address' => array(
                    'email' => $toEmail,
                    'name'  => $toName,
                )
            );
        }
        foreach ($replyToAddresses as $replyToEmail => $replyToName) {
            if ($replyToName){
                $headers['Reply-To'] = sprintf('%s <%s>', $replyToEmail, $replyToName);
            } else {
                $headers['Reply-To'] = $replyToEmail;
            }
        }
        foreach ($ccAddresses as $ccEmail => $ccName) {
            $cc[] = array(
                'email' => $ccEmail,
                'name'  => $ccName,
            );
        }
        foreach ($bccAddresses as $bccEmail => $bccName) {
            $bcc[] = array(
                'email' => $bccEmail,
                'name'  => $bccName,
            );
        }
        $bodyHtml = $bodyText = null;
        if($contentType === 'text/plain'){
            $bodyText = $message->getBody();
        }
        elseif($contentType === 'text/html'){
            $bodyHtml = $message->getBody();
        }
        else{
            $bodyHtml = $message->getBody();
        }
        foreach ($message->getChildren() as $child) {
            if ($child instanceof Swift_Attachment) {
                $attachments[] = array(
                    'type'    => $child->getContentType(),
                    'name'    => $child->getFilename(),
                    'data' => base64_encode($child->getBody())
                );
            } elseif ($child instanceof Swift_MimePart && $this->supportsContentType($child->getContentType())) {
                if ($child->getContentType() == "text/html") {
                    $bodyHtml = $child->getBody();
                } elseif ($child->getContentType() == "text/plain") {
                    $bodyText = $child->getBody();
                }
            }
        }
        if ($message->getHeaders()->has('List-Unsubscribe')) {
            $headers['List-Unsubscribe'] = $message->getHeaders()->get('List-Unsubscribe')->getValue();
        }
        if ($message->getHeaders()->has('X-MC-InlineCSS')) {
            $inlineCss = $message->getHeaders()->get('X-MC-InlineCSS')->getValue();
        }
        if($message->getHeaders()->has('X-MC-Tags')){
            /** @var \Swift_Mime_Headers_UnstructuredHeader $tagsHeader */
            $tagsHeader = $message->getHeaders()->get('X-MC-Tags');
            $tags = explode(',', $tagsHeader->getValue());
        }
        $sparkPostMessage = array(
            'html'       => $bodyHtml,
            'text'       => $bodyText,
            'from'       => $from,
            'subject'    => $message->getSubject(),
            'recipients' => $recipients,
            'cc'         => $cc,
            'bcc'        => $bcc,
            'headers'    => $headers,
            'inline_css' => $inlineCss,
            'tags'       => $tags
        );
        if (count($attachments) > 0) {
            $sparkPostMessage['attachments'] = $attachments;
        }
        return $sparkPostMessage;
    }
    /**
     * @return null|array
     */
    public function getResultApi()
    {
        return $this->apiResponsePayload;
    }
}
