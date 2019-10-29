<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @see         https://github.com/SlowProg/SparkPostSwiftMailer/blob/master/SwiftMailer/SparkPostTransport.php for additional source reference
 */

namespace Mautic\EmailBundle\Swiftmailer\Transport;

use Mautic\EmailBundle\Model\TransportCallback;
use Mautic\EmailBundle\Swiftmailer\Sparkpost\SparkpostFactoryInterface;
use Mautic\LeadBundle\Entity\DoNotContact;
use Psr\Log\LoggerInterface;
use SparkPost\SparkPost;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class SparkpostTransport.
 */
class SparkpostTransport extends AbstractTokenArrayTransport implements \Swift_Transport, TokenTransportInterface, CallbackTransportInterface
{
    /**
     * @var string|null
     */
    private $apiKey;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var TransportCallback
     */
    private $transportCallback;

    /**
     * @var SparkpostFactoryInterface
     */
    private $sparkpostFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param string                    $apiKey
     * @param TranslatorInterface       $translator
     * @param TransportCallback         $transportCallback
     * @param SparkpostFactoryInterface $sparkpostFactory
     * @param LoggerInterface           $logger
     */
    public function __construct(
        $apiKey,
        TranslatorInterface $translator,
        TransportCallback $transportCallback,
        SparkpostFactoryInterface $sparkpostFactory,
        LoggerInterface $logger
    ) {
        $this->setApiKey($apiKey);

        $this->translator        = $translator;
        $this->transportCallback = $transportCallback;
        $this->sparkpostFactory  = $sparkpostFactory;
        $this->logger            = $logger;
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return null|string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Start this Transport mechanism.
     */
    public function start()
    {
        if (empty($this->apiKey)) {
            $this->throwException($this->translator->trans('mautic.email.api_key_required', [], 'validators'));
        }

        $this->started = true;
    }

    /**
     * Creates new SparkPost HTTP client.
     * If no API key is provided then the default one is used.
     *
     * @param string $apiKey
     *
     * @return SparkPost
     */
    protected function createSparkPost($apiKey = null)
    {
        if (null === $apiKey) {
            $apiKey = $this->apiKey;
        }

        return $this->sparkpostFactory->create('', $apiKey);
    }

    /**
     * @param \Swift_Mime_Message $message
     * @param null                $failedRecipients
     *
     * @return int Number of messages sent
     *
     * @throws \Exception
     */
    public function send(\Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $sendCount = 0;
        if ($event = $this->getDispatcher()->createSendEvent($this, $message)) {
            $this->getDispatcher()->dispatchEvent($event, 'beforeSendPerformed');
            if ($event->bubbleCancelled()) {
                return 0;
            }
        }

        try {
            $sparkPostMessage = $this->getSparkPostMessage($message);
            $sparkPostClient  = $this->createSparkPost();

            $this->checkTemplateIsValid($sparkPostClient, $sparkPostMessage);

            $promise  = $sparkPostClient->transmissions->post($sparkPostMessage);
            $response = $promise->wait();
            $body     = $response->getBody();

            if ($errorMessage = $this->getErrorMessageFromResponseBody($body)) {
                $this->processImmediateSendFeedback($sparkPostMessage, $body);
                throw new \Exception($errorMessage);
            }

            $sendCount = $body['results']['total_accepted_recipients'];
        } catch (\Exception $e) {
            $this->throwException($e->getMessage());
        }

        if ($event) {
            if ($sendCount > 0) {
                $event->setResult(\Swift_Events_SendEvent::RESULT_SUCCESS);
            } else {
                $event->setResult(\Swift_Events_SendEvent::RESULT_FAILED);
            }
            $this->getDispatcher()->dispatchEvent($event, 'sendPerformed');
        }

        return $sendCount;
    }

    /**
     * https://jsapi.apiary.io/apis/sparkpostapi/introduction/subaccounts-coming-to-an-api-near-you-in-april!.html.
     *
     * @param \Swift_Mime_Message $message
     *
     * @return array SparkPost Send Message
     *
     * @throws \Exception
     */
    public function getSparkPostMessage(\Swift_Mime_Message $message)
    {
        $tags      = [];
        $inlineCss = null;

        $this->message = $message;
        $metadata      = $this->getMetadata();
        $mauticTokens  = $mergeVars = $mergeVarPlaceholders = [];

        // Sparkpost uses {{ name }} for tokens so Mautic's need to be converted; although using their {{{ }}} syntax to prevent HTML escaping
        if (!empty($metadata)) {
            $metadataSet  = reset($metadata);
            $tokens       = (!empty($metadataSet['tokens'])) ? $metadataSet['tokens'] : [];
            $mauticTokens = array_keys($tokens);

            $mergeVars = $mergeVarPlaceholders = [];
            foreach ($mauticTokens as $token) {
                $mergeVars[$token]            = strtoupper(preg_replace('/[^a-z0-9]+/i', '', $token));
                $mergeVarPlaceholders[$token] = '{{{ '.$mergeVars[$token].' }}}';
            }
        }

        $message = $this->messageToArray($mauticTokens, $mergeVarPlaceholders, true);

        // Sparkpost requires a subject
        if (empty($message['subject'])) {
            throw new \Exception($this->translator->trans('mautic.email.subject.notblank', [], 'validators'));
        }

        if (isset($message['headers']['X-MC-InlineCSS'])) {
            $inlineCss = $message['headers']['X-MC-InlineCSS'];
        }
        if (isset($message['headers']['X-MC-Tags'])) {
            $tags = explode(',', $message['headers']['X-MC-Tags']);
        }

        $recipients = [];
        foreach ($message['recipients']['to'] as $to) {
            $recipient = [
                'address'           => $to,
                'substitution_data' => [],
                'metadata'          => [],
            ];

            if (isset($metadata[$to['email']]['tokens'])) {
                foreach ($metadata[$to['email']]['tokens'] as $token => $value) {
                    $recipient['substitution_data'][$mergeVars[$token]] = $value;
                }

                unset($metadata[$to['email']]['tokens']);
                $recipient['metadata'] = $metadata[$to['email']];
            }

            // Sparkpost requires substitution_data which can be byspassed by using MailHelper::setTo() rather than a Lead via MailHelper::setLead()
            // Without it, Sparkpost returns the error: "field 'substitution_data' is required"
            // But, it can't be an empty array or Sparkpost will return error: field 'substitution_data' is of type 'json_array', but needs to be of type 'json_object'
            if (empty($recipient['substitution_data'])) {
                $recipient['substitution_data'] = new \stdClass();
            }

            // Sparkpost doesn't like empty metadata
            if (empty($recipient['metadata'])) {
                unset($recipient['metadata']);
            }

            $recipients[] = $recipient;

            // CC and BCC fields need to be included as a normal TO address with token duplication
            // https://www.sparkpost.com/docs/faq/cc-bcc-with-rest-api/ - token duplication is not mentioned here
            // See test for CC and BCC too
            foreach (['cc', 'bcc'] as $copyType) {
                if (!empty($message['recipients'][$copyType])) {
                    foreach ($message['recipients'][$copyType] as $email => $content) {
                        $copyRecipient = [
                            'address'   => ['email' => $email],
                            'header_to' => $to['email'],
                        ];

                        if (!empty($recipient['substitution_data'])) {
                            $copyRecipient['substitution_data'] = $recipient['substitution_data'];
                        }

                        $recipients[] = $copyRecipient;
                    }
                }
            }
        }

        $content = [
            'from'    => (!empty($message['from']['name'])) ? $message['from']['name'].' <'.$message['from']['email'].'>'
                : $message['from']['email'],
            'subject' => $message['subject'],
        ];

        if (!empty($message['headers'])) {
            $content['headers'] = $message['headers'];
        }

        // Sparkpost will set parts regardless if they are empty or not
        if (!empty($message['html'])) {
            $content['html'] = $message['html'];
        }

        if (!empty($message['text'])) {
            $content['text'] = $message['text'];
        }

        // Add Reply To
        if (isset($message['replyTo'])) {
            $content['reply_to'] = $message['replyTo']['email'];
        }

        $encoder = new \Swift_Mime_ContentEncoder_Base64ContentEncoder();
        foreach ($this->message->getChildren() as $child) {
            if ($child instanceof \Swift_Image) {
                $content['inline_images'][] = [
                    'type' => $child->getContentType(),
                    'name' => $child->getId(),
                    'data' => $encoder->encodeString($child->getBody()),
                ];
            }
        }

        $sparkPostMessage = [
            'content'    => $content,
            'recipients' => $recipients,
            'inline_css' => $inlineCss,
            'tags'       => $tags,
        ];

        if (!empty($message['attachments'])) {
            foreach ($message['attachments'] as $key => $attachment) {
                $message['attachments'][$key]['data'] = $attachment['content'];
                unset($message['attachments'][$key]['content']);
            }
            $sparkPostMessage['content']['attachments'] = $message['attachments'];
        }

        $sparkPostMessage['options'] = [
            'open_tracking'  => false,
            'click_tracking' => false,
        ];

        return $sparkPostMessage;
    }

    /**
     * @return int
     */
    public function getMaxBatchLimit()
    {
        return 5000;
    }

    /**
     * @param \Swift_Message $message
     * @param int            $toBeAdded
     * @param string         $type
     *
     * @return int
     */
    public function getBatchRecipientCount(\Swift_Message $message, $toBeAdded = 1, $type = 'to')
    {
        return count($message->getTo()) + count($message->getCc()) + count($message->getBcc()) + $toBeAdded;
    }

    /**
     * Returns a "transport" string to match the URL path /mailer/{transport}/callback.
     *
     * @return mixed
     */
    public function getCallbackPath()
    {
        return 'sparkpost';
    }

    /**
     * Handle response.
     *
     * @param Request $request
     */
    public function processCallbackRequest(Request $request)
    {
        $payload = $request->request->all();

        foreach ($payload as $msys) {
            $msys = $msys['msys'];
            if (isset($msys['message_event'])) {
                $event = $msys['message_event'];
            } elseif (isset($msys['unsubscribe_event'])) {
                $event = $msys['unsubscribe_event'];
            } else {
                continue;
            }

            if (isset($event['rcpt_type']) && 'to' !== $event['rcpt_type']) {
                // Ignore cc/bcc

                continue;
            }

            if ('bounce' === $event['type'] && !in_array((int) $event['bounce_class'], [10, 30, 50, 51, 52, 53, 54, 90])) {
                // Only parse hard bounces - https://support.sparkpost.com/customer/portal/articles/1929896-bounce-classification-codes
                continue;
            }

            if (isset($event['rcpt_meta']['hashId']) && $hashId = $event['rcpt_meta']['hashId']) {
                $this->processCallbackByHashId($hashId, $event);

                continue;
            }

            $this->processCallbackByEmailAddress($event['rcpt_to'], $event);
        }
    }

    /**
     * Checks with Sparkpost whether the email template is valid.
     * Substitution data are taken from the first recipient.
     *
     * @param Sparkpost $sparkPostClient
     * @param array     $sparkPostMessage
     *
     * @throws \UnexpectedValueException
     */
    protected function checkTemplateIsValid(Sparkpost $sparkPostClient, array $sparkPostMessage)
    {
        // Take substitution_data from the first recipient.
        if (empty($sparkPostMessage['substitution_data']) && isset($sparkPostMessage['recipients'][0]['substitution_data'])) {
            $sparkPostMessage['substitution_data'] = $sparkPostMessage['recipients'][0]['substitution_data'];
            unset($sparkPostMessage['recipients']);
        }

        $promise  = $sparkPostClient->request('POST', 'utils/content-previewer', $sparkPostMessage);
        $response = $promise->wait();
        $body     = $response->getBody();

        if ($response->getStatusCode() === 403) {
            // We cannot fail as it would be a BC break. Throw a warning and continue.
            $this->logger->warning("The permission 'Templates: Preview' is not enabled. Enable it to let Mautic check email template validity before send.");

            return;
        }

        if ($errorMessage = $this->getErrorMessageFromResponseBody($body)) {
            throw new \UnexpectedValueException($errorMessage);
        }
    }

    /**
     * Check for SparkPost rejection for immediate error messages.
     *
     * @param array $message
     * @param array $response
     */
    private function processImmediateSendFeedback(array $message, array $response)
    {
        if (!empty($response['errors'][0]['code']) && 1902 == (int) $response['errors'][0]['code']) {
            $comments     = $this->getErrorMessageFromResponseBody($response);
            $emailAddress = $message['recipients'][0]['address']['email'];
            $metadata     = $this->getMetadata();

            if (isset($metadata[$emailAddress]) && isset($metadata[$emailAddress]['leadId'])) {
                $emailId = (!empty($metadata[$emailAddress]['emailId'])) ? $metadata[$emailAddress]['emailId'] : null;
                $this->transportCallback->addFailureByContactId($metadata[$emailAddress]['leadId'], $comments, DoNotContact::BOUNCED, $emailId);
            }
        }
    }

    /**
     * Sparkpost renamed the error message property name from 'description' to 'message'.
     * Ensure that we get the error message before and after the change is made.
     *
     * @see https://www.sparkpost.com/blog/error-handling-transmissions-api
     *
     * @param array $response
     *
     * @return string
     */
    private function getErrorMessageFromResponseBody(array $response)
    {
        if (isset($response['errors'][0]['description'])) {
            return $response['errors'][0]['description'];
        } elseif (isset($response['errors'][0]['message'])) {
            return $response['errors'][0]['message'];
        }

        return null;
    }

    /**
     * @param       $hashId
     * @param array $event
     */
    private function processCallbackByHashId($hashId, array $event)
    {
        switch ($event['type']) {
            case 'bounce':
                $this->transportCallback->addFailureByHashId($hashId, $event['raw_reason']);
                break;
            case 'spam_complaint':
                $this->transportCallback->addFailureByHashId($hashId, $event['fbtype'], DoNotContact::UNSUBSCRIBED);
                break;
            case 'out_of_band':
            case 'policy_rejection':
                $this->transportCallback->addFailureByHashId($hashId, $event['raw_reason']);
                break;
            case 'list_unsubscribe':
            case 'link_unsubscribe':
                $this->transportCallback->addFailureByHashId($hashId, 'unsubscribed', DoNotContact::UNSUBSCRIBED);
                break;
        }
    }

    /**
     * @param       $email
     * @param array $event
     */
    private function processCallbackByEmailAddress($email, array $event)
    {
        switch ($event['type']) {
            case 'bounce':
                $this->transportCallback->addFailureByAddress($email, $event['raw_reason']);
                break;
            case 'spam_complaint':
                $this->transportCallback->addFailureByAddress($email, $event['fbtype'], DoNotContact::UNSUBSCRIBED);
                break;
            case 'out_of_band':
            case 'policy_rejection':
                $this->transportCallback->addFailureByAddress($email, $event['raw_reason']);
                break;
            case 'list_unsubscribe':
            case 'link_unsubscribe':
                $this->transportCallback->addFailureByAddress($email, 'unsubscribed', DoNotContact::UNSUBSCRIBED);
                break;
        }
    }
}
