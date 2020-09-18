<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Transport;

use Aws\CommandPool;
use Aws\Credentials\Credentials;
use Aws\Exception\AwsException;
use Aws\ResultInterface;
use Aws\Ses\SesClient;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\Swiftmailer\Amazon\AmazonCallback;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AmazonApiTransport.
 */
class AmazonApiTransport extends AbstractTokenArrayTransport implements \Swift_Transport, CallbackTransportInterface, BounceProcessorInterface, UnsubscriptionProcessorInterface
{
    /**
     * @var string
     */
    private $region;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var int
     */
    private $concurrency;

    /**
     * @var SesClient
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var AmazonCallback
     */
    private $amazonCallback;

    /**
     * AmazonApiTransport constructor.
     */
    public function __construct(LoggerInterface $logger, AmazonCallback $amazonCallback)
    {
        $this->logger         = $logger;
        $this->amazonCallback = $amazonCallback;
    }

    /**
     * @param string $region
     * @param string $otherRegion
     */
    public function setRegion($region, $otherRegion = null)
    {
        $this->region = ('other' === $region) ? $otherRegion : $region;
    }

    /**
     * @param $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @param $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * SES authorization and choice of region.
     *
     * @throws \Exception
     */
    public function start()
    {
        if (!$this->started) {
            $this->client = new SesClient([
                'credentials' => new Credentials(
                    $this->username,
                    $this->password
                ),
                'region'  => $this->region,
                'version' => '2010-12-01',
                'http'    => [
                    'verify'    => false,
                ],
            ]);

            /**
             * AWS SES has a limit of how many messages can be sent in a 24h time slot. The remaining messages are calculated
             * from the api. The transport will fail when the quota is exceeded.
             */
            $quota               = $this->getSesSendQuota();
            $this->concurrency   = floor($quota->get('MaxSendRate'));
            $emailQuotaRemaining = $quota->get('Max24HourSend') - $quota->get('SentLast24Hours');

            if ($emailQuotaRemaining <= 0) {
                $this->logger->error('Your AWS SES quota is currently exceeded, used '.$quota->get('SentLast24Hours').' of '.$quota->get('Max24HourSend'));
                throw new \Exception('Your AWS SES quota is currently exceeded');
            }

            $this->started = true;
        }
    }

    /**
     * @param null $failedRecipients
     *
     * @return int count of recipients
     */
    public function send(\Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $this->message = $message;

        $failedRecipients = (array) $failedRecipients;

        if ($evt = $this->getDispatcher()->createSendEvent($this, $message)) {
            $this->getDispatcher()->dispatchEvent($evt, 'beforeSendPerformed');
            if ($evt->bubbleCancelled()) {
                return 0;
            }
        }

        return $this->sendRawEmail($message, $evt, $failedRecipients);
    }

    /**
     * @param array $failedRecipients
     */
    private function triggerSendError(\Swift_Events_SendEvent $evt, &$failedRecipients)
    {
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

    /**
     * Retrieve the send quota from SES.
     *
     * @return \Aws\Result
     *
     * @throws \Exception
     *
     * @see https://docs.aws.amazon.com/ses/latest/APIReference/API_GetSendQuota.html
     */
    private function getSesSendQuota()
    {
        $this->logger->debug('Retrieving SES quota');
        try {
            return $this->client->getSendQuota();
        } catch (AwsException $e) {
            $this->logger->error('Error retrieving AWS SES quota info: '.$e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @param \Swift_Events_SendEvent @evt
     * @param null $failedRecipients
     *
     * @return int
     */
    public function sendRawEmail(\Swift_Mime_SimpleMessage $message, \Swift_Events_SendEvent $evt, &$failedRecipients = null)
    {
        try {
            $this->start();
            $commands = [];
            foreach ($this->getAmazonMessage($message) as $rawEmail) {
                $commands[] = $this->client->getCommand('sendRawEmail', $rawEmail);
            }
            $pool = new CommandPool($this->client, $commands, [
                'concurrency' => $this->concurrency,
                'fulfilled'   => function (ResultInterface $result, $iteratorId) use ($evt, $failedRecipients) {
                    if ($evt) {
                        $evt->setResult(\Swift_Events_SendEvent::RESULT_SUCCESS);
                        $evt->setFailedRecipients($failedRecipients);
                        $this->getDispatcher()->dispatchEvent($evt, 'sendPerformed');
                    }
                },
                'rejected' => function (AwsException $reason, $iteratorId) use ($evt) {
                    $failedRecipients = [];
                    $this->triggerSendError($evt, $failedRecipients);
                },
            ]);
            $promise = $pool->promise();
            $promise->wait();

            return count($commands);
        } catch (\Exception $e) {
            $this->triggerSendError($evt, $failedRecipients);
            $message->generateId();
            $this->throwException($e->getMessage());
        }

        return 1;
    }

    /**
     * @return array
     */
    public function getAmazonMessage(\Swift_Mime_SimpleMessage $message)
    {
        $this->message = $message;
        $metadata      = $this->getMetadata();
        $emailBody     = $this->message->getBody();

        if (!empty($metadata)) {
            $metadataSet  = reset($metadata);
            $tokens       = (!empty($metadataSet['tokens'])) ? $metadataSet['tokens'] : [];
            $mauticTokens = array_keys($tokens);
        }

        foreach ($metadata as $recipient => $mailData) {
            $this->message->setBody($emailBody);
            $msg        = $this->messageToArray($mauticTokens, $mailData['tokens'], true);
            $rawMessage = $this->buildRawMessage($msg, $recipient);
            $payload    = [
                'Source'       => $msg['from']['email'],
                'Destinations' => [$recipient],
                'RawMessage'   => [
                    'Data' => $rawMessage,
                ],
            ];

            yield $payload;
        }
    }

    /**
     * @param $msg
     * @param $recipient
     *
     * @return string
     */
     public function buildRawMessage($msg, $recipient)
     {
       $separator           = md5(time());
       $separator_multipart = md5($msg['subject'].time());
       $message             = "MIME-Version: 1.0\n";
       $message .= 'Subject: '. mb_encode_mimeheader($msg['subject'], 'UTF-8') ."\n";
       $message .= 'From: '.mb_encode_mimeheader($msg['from']['name']).' <'.$msg['from']['email'].">\n";
       $message .= "To: $recipient\n";
       if (count($msg['recipients']['cc']) > 0) {
         $message .= 'Cc: '.implode(',', array_keys($msg['recipients']['cc']))."\n";
       }
       if (count($msg['recipients']['bcc']) > 0) {
         $message .= 'Bcc: '.implode(',', array_keys($msg['recipients']['bcc']))."\n";
       }
       if (isset($msg['replyTo'])) {
         $message .= 'Reply-To: '.$msg['replyTo']['email']."\n";
       }
       if (isset($msg['returnPath'])) {
         $message .= 'Return-Path: '.$msg['returnPath']."\n";
       }
       if (isset($msg['headers'])) {
         foreach ($msg['headers'] as $key => $value) {
           $message .= "$key: ".$value."\n";
         }
       }
       
       if (count($msg['attachments']) > 0) {
         $message .= "Content-Type: multipart/mixed; boundary=\"$separator_multipart\"\n";
         $message .= "\n--$separator_multipart\n";
       }
       
       $message .= "Content-Type: multipart/alternative; boundary=\"$separator\"\n";
       if (isset($msg['text']) && strlen($msg['text']) > 0) {
         $message .= "\n--$separator\n";
         $message .= "Content-Type: text/plain; charset=\"UTF-8\"\n";
         $message .= "Content-Transfer-Encoding: base64\n";
         $message .= "\n".wordwrap(base64_encode($msg['text']), 76, "\n", true)."\n";
       }
       $message .= "\n--$separator\n";
       $message .= "Content-Type: text/html; charset=\"UTF-8\"\n";
       $message .= "\n".$msg['html']."\n";
       $message .= "\n--$separator--\n";
       
       if (count($msg['attachments']) > 0) {
         foreach ($msg['attachments'] as $attachment) {
           $message .= "--$separator_multipart\n";
           $message .= 'Content-Type: '.$attachment['type'].'; name="'.$attachment['name']."\"\n";
           $message .= 'Content-Disposition: attachment; filename="'.$attachment['name']."\"\n";
           $message .= "Content-Transfer-Encoding: base64\n";
           $message .= "\n".$attachment['content']."\n";
         }
       }
       
       return $message."--$separator_multipart--";
     }

    /**
     * Return the max number of to addresses allowed per batch
     * No limit used since CommandPool handles the rate limiting.
     *
     * @return int
     */
    public function getMaxBatchLimit()
    {
        return 0;
    }

    /**
     * Get the count for the max number of recipients per batch.
     *
     * @param int    $toBeAdded Number of emails about to be added
     * @param string $type      Type of emails being added (to, cc, bcc)
     *
     * @return int
     */
    public function getBatchRecipientCount(\Swift_Message $message, $toBeAdded = 1, $type = 'to')
    {
        $toCount  = is_countable($message->getTo()) ? count($message->getTo()) : 0;
        $ccCount  = is_countable($message->getCc()) ? count($message->getCc()) : 0;
        $bccCount = is_countable($message->getBcc()) ? count($message->getBcc()) : 0;

        return $toCount + $ccCount + $bccCount + $toBeAdded;
    }

    /**
     * Returns a "transport" string to match the URL path /mailer/{transport}/callback.
     *
     * @return string
     */
    public function getCallbackPath()
    {
        return 'amazon_api';
    }

    /**
     * Handle bounces & complaints from Amazon.
     */
    public function processCallbackRequest(Request $request)
    {
        $this->amazonCallback->processCallbackRequest($request);
    }

    public function processBounce(Message $message)
    {
        $this->amazonCallback->processBounce($message);
    }

    public function processUnsubscription(Message $message)
    {
        $this->amazonCallback->processUnsubscription($message);
    }
}
