<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 */

namespace Mautic\EmailBundle\Swiftmailer\Transport;

use Aws\CommandPool;
use Aws\Credentials\Credentials;
use Aws\Exception\AwsException;
use Aws\ResultInterface;
use Aws\SesV2\Exception\SesV2Exception;
use Aws\SesV2\SesV2Client;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Helper\PlainTextMessageHelper;
use Mautic\EmailBundle\Swiftmailer\Amazon\AmazonCallback;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

class AmazonApiTransport extends AbstractTokenArrayTransport implements \Swift_Transport, TokenTransportInterface, CallbackTransportInterface
{
    /**
     * @var string|null
     */
    private $region;

    /**
     * @var string|null
     */
    private $username;

    /**
     * @var string|null
     */
    private $password;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SesV2Client
     */
    private $amazonClient;

    /**
     * @var AmazonCallback
     */
    private $amazonCallback;

    /**
     * @var int
     */
    private $concurrency;

    /**
     * @var Aws\CommandInterface | Psr\Http\Message\RequestInterface
     */
    private $handler;

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * @param string $region
     * @param string $otherRegion
     */
    public function setRegion($region, $otherRegion = null)
    {
        $this->region = ('other' === $region) ? $otherRegion : $region;
    }

    /**
     * @return string|null
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @param $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return string|null
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return string|null
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param $handler
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;
    }

    /**
     * @return object|null
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * @param $debug
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * @return bool|null
     */
    public function getDebug()
    {
        return $this->debug;
    }

    public function __construct(
        TranslatorInterface $translator,
        AmazonCallback $amazonCallback,
        LoggerInterface $logger
    ) {
        $this->amazonCallback    = $amazonCallback;
        $this->translator        = $translator;
        $this->logger            = $logger;
    }

    public function start()
    {
        if (empty($this->region) || empty($this->username) || empty($this->password)) {
            $this->throwException($this->translator->trans('mautic.email.api_key_required', [], 'validators'));
        }

        if (!$this->started) {
            $this->amazonClient  = $this->createAmazonClient();

            $account             = $this->amazonClient->getAccount();
            $emailQuotaRemaining = $account->get('SendQuota')['Max24HourSend'] - $account->get('SendQuota')['SentLast24Hours'];

            if (!$account->get('SendingEnabled')) {
                $this->logger->error('Your AWS SES is not enabled for sending');
                throw new \Exception('Your AWS SES is not enabled for sending');
            }

            if (!$account->get('ProductionAccessEnabled')) {
                $this->logger->info('Your AWS SES is in sandbox mode, consider moving it to production state');
            }

            if ($emailQuotaRemaining <= 0) {
                $this->logger->error('Your AWS SES quota is currently exceeded, used '.$account->get('SentLast24Hours').' of '.$account->get('Max24HourSend'));
                throw new \Exception('Your AWS SES quota is currently exceeded');
            }

            $this->concurrency   = floor($account->get('SendQuota')['MaxSendRate']);

            $this->started = true;
        }
    }

    public function createAmazonClient()
    {
        $config  = [
            'version'     => '2019-09-27',
            'region'      => $this->region,
            'credentials' => new Credentials(
                $this->username,
                $this->password
            ),
        ];

        if ($this->handler) {
            $config['handler'] = $this->handler;
        }

        if ($this->debug) {
            $config['debug'] = [
                'logfn' => function ($msg) {
                    $this->logger->debug($msg);
                },
                'http'        => true,
                'stream_size' => '0',
            ];
        }

        return new SesV2Client($config);
    }

    /**
     * @param null $failedRecipients
     *
     * @return int Number of messages sent
     *
     * @throws \Exception
     */
    public function send(\Swift_Mime_SimpleMessage $toSendMessage, &$failedRecipients = null)
    {
        $this->message    = $toSendMessage;
        $failedRecipients = (array) $failedRecipients;

        if ($evt = $this->getDispatcher()->createSendEvent($this, $toSendMessage)) {
            $this->getDispatcher()->dispatchEvent($evt, 'beforeSendPerformed');
            if ($evt->bubbleCancelled()) {
                return 0;
            }
        }
        $count = $this->getBatchRecipientCount($toSendMessage);

        try {
            $this->start();
            $commands = [];
            foreach ($this->getAmazonMessage($toSendMessage) as $rawEmail) {
                $commands[] = $this->amazonClient->getCommand('sendEmail', $rawEmail);
            }
            $pool = new CommandPool($this->amazonClient, $commands, [
                'concurrency' => $this->concurrency,
                'fulfilled'   => function (ResultInterface $result, $iteratorId) use ($evt, $failedRecipients) {
                    if ($evt) {
                        // $this->logger->info("SES Result: " . $result->get('MessageId'));
                        $evt->setResult(\Swift_Events_SendEvent::RESULT_SUCCESS);
                        $evt->setFailedRecipients($failedRecipients);
                        $this->getDispatcher()->dispatchEvent($evt, 'sendPerformed');
                    }
                },
                'rejected' => function (AwsException $reason, $iteratorId) use ($evt) {
                    $failedRecipients = [];
                    $this->triggerSendError($evt, $failedRecipients, $reason->getAwsErrorMessage());
                },
            ]);
            $promise = $pool->promise();
            $promise->wait();

            return count($commands);
        } catch (SesV2Exception $e) {
            $this->triggerSendError($evt, $failedRecipients, $e->getMessage());
            $this->throwException($e->getMessage());
        } catch (\Exception $e) {
            $this->triggerSendError($evt, $failedRecipients, $e->getMessage());
            $this->throwException($e->getMessage());
        }

        return 1;
    }

    private function triggerSendError(\Swift_Events_SendEvent $evt, &$failedRecipients, $reason)
    {
        $this->logger->error('SES API Error: '.$reason);

        $failedRecipients = array_merge(
            $failedRecipients,
            array_keys((array) $this->message->getTo()),
            array_keys((array) $this->message->getCc()),
            array_keys((array) $this->message->getBcc())
        );

        if ($evt) {
            $evt->setResult(\Swift_Events_SendEvent::RESULT_FAILED);
            $evt->setFailedRecipients($failedRecipients);
            $this->getDispatcher()->dispatchEvent($evt, 'sendPerformed');
        }
    }

    /*
     * @return array Amazon Send Message
     *
     * @throws \Exception
     */
    public function getAmazonMessage(\Swift_Mime_SimpleMessage $message)
    {
        /**
         * Three ways to send the email
         * Simple:  A standard email message. When you create this type of message, you specify the sender, the recipient, and the message body, and Amazon SES assembles the message for you.
         * Raw : A raw, MIME-formatted email message. When you send this type of email, you have to specify all of the message headers, as well as the message body. You can use this message type to send messages that contain attachments. The message that you specify has to be a valid MIME message.
         * Template: A message that contains personalization tags. When you send this type of email, Amazon SES API v2 automatically replaces the tags with values that you specify.
         *
         * In Mautic we need to use RAW all the time because we inject custom headers all the time, so templates and simple are not useful
         * If in the future AWS allow custom headers in templates or simple emails we should consider them
         *
         * Since we are using SES RAW method, we need to create a seperate message each time we send out an email
         * In case SES changes their API this is the only function that needs to be changed to accomrdate sending a template
         */
        $this->message    = $message;
        $metadata         = $this->getMetadata();
        $emailBody        = $this->message->getBody();
        $emailSubject     = $this->message->getSubject();
        $emailText        = PlainTextMessageHelper::getPlainTextFromMessage($message);
        $sesArray         = [];

        if (empty($metadata)) {
            /**
             * This is a queued message, all the information are included
             * in the $message object
             * just construct the $sesArray.
             */
            $from      = $message->getFrom();
            $fromEmail = current(array_keys($from));
            $fromName  = $from[$fromEmail];

            $sesArray['FromEmailAddress'] =  (!empty($fromName)) ? mb_encode_mimeheader($fromName).' <'.$fromEmail.'>' : $fromEmail;
            $to                           = $message->getTo();
            if (!empty($to)) {
                $sesArray['Destination']['ToAddresses'] = array_keys($to);
            }

            $cc = $message->getCc();
            if (!empty($cc)) {
                $sesArray['Destination']['CcAddresses'] = array_keys($cc);
            }
            $bcc = $message->getBcc();
            if (!empty($bcc)) {
                $sesArray['Destination']['BccAddresses'] = array_keys($bcc);
            }
            $replyTo = $message->getReplyTo();
            if (!empty($replyTo)) {
                $sesArray['ReplyToAddresses'] = [key($replyTo)];
            }
            $headers            = $message->getHeaders();
            if ($headers->has('X-SES-CONFIGURATION-SET')) {
                $sesArray['ConfigurationSetName'] = $headers->get('X-SES-CONFIGURATION-SET');
            }

            $sesArray['Content']['Raw']['Data'] = $message->toString();
            yield $sesArray;
        } else {
            /**
             * This is a message with tokens.
             */
            $mauticTokens  = [];
            $metadataSet   = reset($metadata);
            $tokens        = (!empty($metadataSet['tokens'])) ? $metadataSet['tokens'] : [];
            $mauticTokens  = array_keys($tokens);
            foreach ($metadata as $recipient => $mailData) {
                // Reset the parts of the email that has tokens
                $this->message->setSubject($emailSubject);
                $this->message->setBody($emailBody);
                $this->setPlainTextToMessage($this->message, $emailText);
                // Convert the message to array to get the values
                $tokenizedMessage                                = $this->messageToArray($mauticTokens, $mailData['tokens'], false);
                $toSendMessage                                   = (new \Swift_Message());
                $toSendMessage->setSubject($tokenizedMessage['subject']);
                $toSendMessage->setFrom([$tokenizedMessage['from']['email'] => $tokenizedMessage['from']['name']]);
                $sesArray['FromEmailAddress'] =  (!empty($tokenizedMessage['from']['name'])) ? mb_encode_mimeheader($tokenizedMessage['from']['name']).' <'.$tokenizedMessage['from']['email'].'>' : $tokenizedMessage['from']['email'];
                $toSendMessage->setTo([$recipient]);
                $sesArray['Destination']['ToAddresses'] = [$recipient];
                if (isset($tokenizedMessage['text']) && strlen($tokenizedMessage['text']) > 0) {
                    $toSendMessage->addPart($tokenizedMessage['text'], 'text/plain');
                }

                if (isset($tokenizedMessage['html']) && strlen($tokenizedMessage['html']) > 0) {
                    $toSendMessage->addPart($tokenizedMessage['html'], 'text/html');
                }
                if (isset($tokenizedMessage['headers'])) {
                    $headers = $toSendMessage->getHeaders();
                    foreach ($tokenizedMessage['headers'] as $key => $value) {
                        $headers->addTextHeader($key, $value);
                    }
                }

                if (count($tokenizedMessage['recipients']['cc']) > 0) {
                    $cc = array_keys($tokenizedMessage['recipients']['cc']);
                    $toSendMessage->setCc($cc);
                    $sesArray['Destination']['CcAddresses'] = $cc;
                }

                if (count($tokenizedMessage['recipients']['bcc']) > 0) {
                    $bcc = array_keys($tokenizedMessage['recipients']['bcc']);
                    $toSendMessage->setBcc($bcc);
                    $sesArray['Destination']['BccAddresses'] = $bcc;
                }

                if (isset($tokenizedMessage['replyTo'])) {
                    $toSendMessage->setReplyTo([$tokenizedMessage['replyTo']['email']]);
                    $sesArray['ReplyToAddresses'] = [$tokenizedMessage['replyTo']['email']];
                }
                if (isset($tokenizedMessage['headers']['X-SES-CONFIGURATION-SET'])) {
                    $sesArray['ConfigurationSetName'] = $tokenizedMessage['headers']['X-SES-CONFIGURATION-SET'];
                }

                if (count($tokenizedMessage['file_attachments']) > 0) {
                    foreach ($tokenizedMessage['file_attachments'] as $attachment) {
                        $fileAttach = \Swift_Attachment::fromPath($attachment['filePath']);
                        $fileAttach->setFilename($attachment['fileName']);
                        $fileAttach->setContentType($attachment['contentType']);
                        $toSendMessage->attach($fileAttach);
                    }
                }
                if (count($tokenizedMessage['binary_attachments']) > 0) {
                    foreach ($tokenizedMessage['binary_attachments'] as $attachment) {
                        $fileAttach = new \Swift_Attachment($attachment['content'], $attachment['name'], $attachment['type']);
                        $toSendMessage->attach($fileAttach);
                    }
                }
                $sesArray['Content']['Raw']['Data'] = $toSendMessage->toString();
                yield $sesArray;
            }
        }
    }

    /**
     * Set plain text to a message.
     *
     * @return bool
     */
    private function setPlainTextToMessage(\Swift_Mime_SimpleMessage $message, $text)
    {
        $children = (array) $message->getChildren();

        foreach ($children as $child) {
            $childType = $child->getContentType();
            if ('text/plain' === $childType && $child instanceof \Swift_MimePart) {
                $child->setBody($text);
            }
        }
    }

    /**
     * @return int
     */
    public function getMaxBatchLimit()
    {
        return 0;
    }

    /**
     * @param int    $toBeAdded
     * @param string $type
     */
    public function getBatchRecipientCount(\Swift_Message $toSendMessage, $toBeAdded = 1, $type = 'to'): int
    {
        // These getters could return null
        $toCount  = $toSendMessage->getTo() ? count($toSendMessage->getTo()) : 0;
        $ccCount  = $toSendMessage->getCc() ? count($toSendMessage->getCc()) : 0;
        $bccCount = $toSendMessage->getBcc() ? count($toSendMessage->getBcc()) : 0;

        return $toCount + $ccCount + $bccCount + $toBeAdded;
    }

    /**
     * Returns a "transport" string to match the URL path /mailer/{transport}/callback.
     *
     * @return mixed
     */
    public function getCallbackPath()
    {
        return 'amazon_api';
    }

    /**
     * Handle response.
     */
    public function processCallbackRequest(Request $request)
    {
        $this->amazonCallback->processCallbackRequest($request);
    }

    /**
     * @return bool
     */
    public function ping()
    {
        return true;
    }
}
