<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
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
use bandwidthThrottle\tokenBucket\BlockingConsumer;
use bandwidthThrottle\tokenBucket\Rate;
use bandwidthThrottle\tokenBucket\storage\SingleProcessStorage;
use bandwidthThrottle\tokenBucket\storage\StorageException;
use bandwidthThrottle\tokenBucket\TokenBucket;
use Joomla\Http\Http;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\Swiftmailer\Amazon\AmazonCallback;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class AmazonApiTransport.
 */
class AmazonApiTransport extends AbstractTokenArrayTransport implements \Swift_Transport, TokenTransportInterface, CallbackTransportInterface, BounceProcessorInterface, UnsubscriptionProcessorInterface
{
    /**
     * From address for SNS email.
     */
    const SNS_ADDRESS = 'no-reply@sns.amazonaws.com';

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
     * @var BlockingConsumer
     */
    private $createTemplateBucketConsumer;

    /**
     * @var BlockingConsumer
     */
    private $sendTemplateBucketConsumer;

    /**
     * @var array
     */
    private $templateCache;

    /**
     * AmazonApiTransport constructor.
     */
    public function __construct(Http $httpClient, LoggerInterface $logger, TranslatorInterface $translator, AmazonCallback $amazonCallback)
    {
        $this->logger         = $logger;
        $this->amazonCallback = $amazonCallback;
        $this->templateCache  = [];
    }

    public function __destruct()
    {
        if (count($this->templateCache)) {
            $this->logger->debug('Deleting SES templates that were created in this session');
            foreach ($this->templateCache as $templateName) {
                $this->deleteSesTemplate($templateName);
            }
        }
    }

    /**
     * @return string $region
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @param string $region
     */
    public function setRegion($region)
    {
        $this->region = $region;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * SES authorization and choice of region
     * Initializing of TokenBucket.
     *
     * @throws Exception
     */
    public function start()
    {
        if (!$this->started) {
            $this->client = new SesClient([
                'credentials' => new Credentials(
                    $this->getUsername(),
                    $this->getPassword()
                ),
                'region'  => $this->getRegion(),
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
                throw new Exception('Your AWS SES quota is currently exceeded');
            }

            /*
             * initialize throttle token buckets
             */
            $this->initializeThrottles();

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
        $count = $this->getBatchRecipientCount($message);

        /*
         * If there is an attachment, send mail using sendRawEmail method
         * current sendBulkTemplatedEmail method doesn't support attachments
         */
        if (!empty($message->getAttachments())) {
            return $this->sendRawEmail($message, $evt, $failedRecipients);
        }

        list($amazonTemplate, $amazonMessage) = $this->constructSesTemplateAndMessage($message);

        try {
            $this->start();

            $this->createSesTemplate($amazonTemplate);

            $this->sendSesBulkTemplatedEmail($count, $amazonMessage);

            if ($evt) {
                $evt->setResult(\Swift_Events_SendEvent::RESULT_SUCCESS);
                $evt->setFailedRecipients($failedRecipients);
                $this->getDispatcher()->dispatchEvent($evt, 'sendPerformed');
            }

            return $count;
        } catch (AwsException $e) {
            $this->triggerSendError($evt, $failedRecipients);
            $message->generateId();

            $this->throwException($e->getAwsErrorMessage());
        } catch (Exception $e) {
            $this->triggerSendError($evt, $failedRecipients);
            $message->generateId();

            $this->throwException($e->getMessage());
        }

        return 0;
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
     * Initialize the token buckets for throttling.
     *
     * @throws Exception
     */
    private function initializeThrottles()
    {
        try {
            /**
             * SES limits creating templates to approximately one per second.
             */
            $storageCreate                      = new SingleProcessStorage();
            $rateCreate                         = new Rate(1, Rate::SECOND);
            $bucketCreate                       = new TokenBucket(1, $rateCreate, $storageCreate);
            $this->createTemplateBucketConsumer = new BlockingConsumer($bucketCreate);
            $bucketCreate->bootstrap(1);

            /**
             * SES limits sending emails based on requested account-level limits.
             */
            $storageSend                      = new SingleProcessStorage();
            $rateSend                         = new Rate($this->concurrency, Rate::SECOND);
            $bucketSend                       = new TokenBucket($this->concurrency, $rateSend, $storageSend);
            $this->sendTemplateBucketConsumer = new BlockingConsumer($bucketSend);
            $bucketSend->bootstrap($this->concurrency);
        } catch (\InvalidArgumentException $e) {
            $this->logger->error('error configuring token buckets: '.$e->getMessage());
            throw new Exception($e->getMessage());
        } catch (StorageException $e) {
            $this->logger->error('error bootstrapping token buckets: '.$e->getMessage());
            throw new Exception($e->getMessage());
        } catch (Exception $e) {
            $this->logger->error('error initializing token buckets: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Retrieve the send quota from SES.
     *
     * @return \Aws\Result
     *
     * @throws Exception
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
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param array $template
     *
     * @return \Aws\Result|null
     *
     * @throws Exception
     *
     * @see https://docs.aws.amazon.com/ses/latest/APIReference/API_CreateTemplate.html
     */
    private function createSesTemplate($template)
    {
        $templateName = $template['TemplateName'];

        $this->logger->debug('Creating SES template: '.$templateName);

        /*
         * reuse an existing template if we have created one
         */
        if (false !== array_search($templateName, $this->templateCache)) {
            $this->logger->debug('Template '.$templateName.' already exists in cache');

            return null;
        }

        /*
         * wait for a throttle token
         */
        $this->createTemplateBucketConsumer->consume(1);

        try {
            $result = $this->client->createTemplate(['Template' => $template]);
        } catch (AwsException $e) {
            switch ($e->getAwsErrorCode()) {
                case 'AlreadyExists':
                    $this->logger->debug('Exception creating template: '.$templateName.', '.$e->getAwsErrorCode().', '.$e->getAwsErrorMessage().', ignoring');
                    break;
                default:
                    $this->logger->error('Exception creating template: '.$templateName.', '.$e->getAwsErrorCode().', '.$e->getAwsErrorMessage());
                    throw new Exception($e->getMessage());
            }
        }

        /*
         * store the name of this template so that we can delete it when we are done sending
         */
        $this->templateCache[] = $templateName;

        return $result;
    }

    /**
     * @param string $templateName
     *
     * @return \Aws\Result
     *
     * @throws Exception
     *
     * @see https://docs.aws.amazon.com/ses/latest/APIReference/API_DeleteTemplate.html
     */
    private function deleteSesTemplate($templateName)
    {
        $this->logger->debug('Deleting SES template: '.$templateName);

        try {
            return $this->client->deleteTemplate(['TemplateName' => $templateName]);
        } catch (AwsException $e) {
            $this->logger->error('Exception deleting template: '.$templateName.', '.$e->getAwsErrorCode().', '.$e->getAwsErrorMessage());
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param int   $count   number of recipients for us to consume from the ticket bucket
     * @param array $message
     *
     * @return \Aws\Result
     *
     * @throws Exception
     *
     * @see https://docs.aws.amazon.com/ses/latest/APIReference/API_SendBulkTemplatedEmail.html
     */
    private function sendSesBulkTemplatedEmail($count, $message)
    {
        $this->logger->debug('Sending SES template: '.$message['Template'].' to '.$count.' recipients');

        // wait for a throttle token
        $this->sendTemplateBucketConsumer->consume($count);

        try {
            return $this->client->sendBulkTemplatedEmail($message);
        } catch (AwsException $e) {
            $this->logger->error('Exception sending email template: '.$e->getAwsErrorCode().', '.$e->getAwsErrorMessage());
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Parse message into a template and recipients with their respective replacement tokens.
     *
     * @return array of a template and a message
     */
    private function constructSesTemplateAndMessage(\Swift_Mime_SimpleMessage $message)
    {
        $this->message = $message;
        $metadata      = $this->getMetadata();
        $messageArray  = [];

        if (!empty($metadata)) {
            $metadataSet  = reset($metadata);
            $emailId      = $metadataSet['emailId'];
            $tokens       = (!empty($metadataSet['tokens'])) ? $metadataSet['tokens'] : [];
            $mauticTokens = array_keys($tokens);
            $tokenReplace = $amazonTokens = [];
            foreach ($tokens as $search => $token) {
                $tokenKey              = preg_replace('/[^\da-z]/i', '_', trim($search, '{}'));
                $tokenReplace[$search] = '{{'.$tokenKey.'}}';
                $amazonTokens[$search] = $tokenKey;
            }
            $messageArray = $this->messageToArray($mauticTokens, $tokenReplace, true);
        }

        $CcAddresses = [];
        if (count($messageArray['recipients']['cc']) > 0) {
            $CcAddresses = array_keys($messageArray['recipients']['cc']);
        }

        $BccAddresses = [];
        if (count($messageArray['recipients']['bcc']) > 0) {
            $BccAddresses = array_keys($messageArray['recipients']['bcc']);
        }

        $replyToAddresses = [];
        if (isset($messageArray['replyTo']['email'])) {
            $replyToAddresses = [$messageArray['replyTo']['email']];
        }

        $ConfigurationSetName = null;
        if (isset($messageArray['headers']['X-SES-CONFIGURATION-SET'])) {
            $ConfigurationSetName = $messageArray['headers']['X-SES-CONFIGURATION-SET'];
        }

        //build amazon ses template array
        $amazonTemplate = [
            'TemplateName' => 'MauticTemplate-'.$emailId.'-'.md5($messageArray['subject'].$messageArray['html'].'-'.getmypid()), //unique template name
            'SubjectPart'  => $messageArray['subject'],
            'TextPart'     => $messageArray['text'],
            'HtmlPart'     => $messageArray['html'],
        ];

        $destinations = [];
        foreach ($metadata as $recipient => $mailData) {
            $ReplacementTemplateData = [];
            foreach ($mailData['tokens'] as $token => $tokenData) {
                $ReplacementTemplateData[$amazonTokens[$token]] = $tokenData;
            }

            $destinations[] = [
                'Destination' => [
                    'BccAddresses' => $BccAddresses,
                    'CcAddresses'  => $CcAddresses,
                    'ToAddresses'  => [$recipient],
                ],
                'ReplacementTemplateData' => \GuzzleHttp\json_encode($ReplacementTemplateData),
            ];
        }

        //build amazon ses message array
        $amazonMessage = [
            'ConfigurationSetName' => $ConfigurationSetName,
            'DefaultTemplateData'  => $destinations[0]['ReplacementTemplateData'],
            'Destinations'         => $destinations,
            'Source'               => $messageArray['from']['email'],
            'ReplyToAddresses'     => $replyToAddresses,
            'Template'             => $amazonTemplate['TemplateName'],
        ];

        if (isset($messageArray['returnPath'])) {
            $amazonMessage['ReturnPath'] = $messageArray['returnPath'];
        }

        if (isset($messageArray['from']['name']) && '' !== trim($messageArray['from']['name'])) {
            $amazonMessage['Source'] = '"'.$messageArray['from']['name'].'" <'.$messageArray['from']['email'].'>';
        }

        return [$amazonTemplate, $amazonMessage];
    }

    /**
     * @param \Swift_Events_SendEvent @evt
     * @param null $failedRecipients
     *
     * @return array
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
                    $this->triggerSendError($evt, []);
                },
            ]);
            $promise = $pool->promise();
            $promise->wait();

            return count($commands);
        } catch (Exception $e) {
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
     * @param type $msg
     * @param type $recipient
     *
     * @return string
     */
    public function buildRawMessage($msg, $recipient)
    {
        $separator           = md5(time());
        $separator_multipart = md5($msg['subject'].time());
        $message             = "MIME-Version: 1.0\n";
        $message .= 'Subject: '.$msg['subject']."\n";
        $message .= 'From: '.$msg['from']['name'].' <'.$msg['from']['email'].">\n";
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
        $message .= "Content-Type: multipart/mixed; boundary=\"$separator_multipart\"\n";
        if (isset($msg['headers'])) {
            foreach ($msg['headers'] as $key => $value) {
                $message .= "$key: ".$value."\n";
            }
        }
        $message .= "\n--$separator_multipart\n";

        $message .= "Content-Type: multipart/alternative; boundary=\"$separator\"\n";
        if (isset($msg['text']) && strlen($msg['text']) > 0) {
            $message .= "\n--$separator\n";
            $message .= "Content-Type: text/plain; charset=\"UTF-8\"\n";
            $message .= "Content-Transfer-Encoding: base64\n";
            $message .= "\n".wordwrap(base64_encode($msg['text']), 78, "\n", true)."\n";
        }
        $message .= "\n--$separator\n";
        $message .= "Content-Type: text/html; charset=\"UTF-8\"\n";
        $message .= "\n".$msg['html']."\n";
        $message .= "\n--$separator--\n";

        foreach ($msg['attachments'] as $attachment) {
            $message .= "--$separator_multipart\n";
            $message .= 'Content-Type: '.$attachment['type'].'; name="'.$attachment['name']."\"\n";
            $message .= 'Content-Disposition: attachment; filename="'.$attachment['name']."\"\n";
            $message .= "Content-Transfer-Encoding: base64\n";
            $message .= "\n".$attachment['content']."\n";
        }

        return $message."--$separator_multipart--";
    }

    /**
     * Return the max number of to addresses allowed per batch.
     *
     * @return int
     *
     * @see https://docs.aws.amazon.com/ses/latest/DeveloperGuide/quotas.html
     */
    public function getMaxBatchLimit()
    {
        return 50;
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

    /**
     * Process json request from Amazon SES.
     */
    public function processJsonPayload(array $payload)
    {
        $this->amazonCallback->processJsonPayload($payload);
    }

    public function processBounce(Message $message)
    {
        $this->amazonCallback->processBounce($message);
    }

    public function processUnsubscription(Message $message)
    {
        $this->amazonCallback->processUnsubscription($message);
    }

    public function getSnsPayload($body)
    {
        $this->amazonCallback->getSnsPayload($body);
    }
}
