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
use Joomla\Http\Exception\UnexpectedResponseException;
use Joomla\Http\Http;
use Mautic\EmailBundle\Model\TransportCallback;
use Mautic\EmailBundle\MonitoredEmail\Exception\BounceNotFound;
use Mautic\EmailBundle\MonitoredEmail\Exception\UnsubscriptionNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\BouncedEmail;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition\Category;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition\Type;
use Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription\UnsubscribedEmail;
use Mautic\LeadBundle\Entity\DoNotContact;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Translation\TranslatorInterface;
use Mautic\EmailBundle\Swiftmailer\Exception\AmazonSesQuotaExceedException;

/**
 * Class AmazonTransport.
 */
class AmazonTransport extends AbstractTokenArrayTransport implements \Swift_Transport, InterfaceTokenTransport, CallbackTransportInterface, BounceProcessorInterface, UnsubscriptionProcessorInterface
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
     * @var Http
     */
    private $httpClient;

    /**
     * @var TransportCallback
     */
    private $transportCallback;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * AmazonTransport constructor.
     *
     * @param Http                $httpClient
     * @param LoggerInterface     $logger
     * @param TranslatorInterface $translator
     * @param TransportCallback   $transportCallback
     */
    public function __construct(Http $httpClient, LoggerInterface $logger, TranslatorInterface $translator, TransportCallback $transportCallback)
    {
        $this->logger            = $logger;
        $this->translator        = $translator;
        $this->httpClient        = $httpClient;
        $this->transportCallback = $transportCallback;
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
     * @return SesClient
     *
     * @throws \Exception
     */
    public function start()
    {
        $client = new SesClient([
            'credentials' => new Credentials(
                $this->getUsername(),
                $this->getPassword()
            ),
            'region'  => $this->getRegion(),
            'version' => '2010-12-01',
        ]);

        /**
         * AWS SES has a limit of how many messages can be sent in a 24h time slot. The remaining messages are calculated
         * from the api. The transport will fail when the quota is exceeded.
         */
        $quota               = $client->getSendQuota();
        $this->concurrency   = floor($quota->get('MaxSendRate'));
        $emailQuoteRemaining = $quota->get('Max24HourSend') - $quota->get('SentLast24Hours');
        if ($emailQuoteRemaining > 0) {
            $this->started = true;
        } else {
            throw new AmazonSesQuotaExceedException('Your AWS SES quota is exceeded');
        }

        return $client;
    }

    /**
     * @param \Swift_Mime_Message $message
     * @param null                $failedRecipients
     *
     * @return array
     */
    public function send(\Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $failedRecipients = (array) $failedRecipients;

        if ($evt = $this->getDispatcher()->createSendEvent($this, $message)) {
            $this->getDispatcher()->dispatchEvent($evt, 'beforeSendPerformed');
            if ($evt->bubbleCancelled()) {
                return 0;
            }
        }
        $count = $this->getBatchRecipientCount($message);

        //If found any attachment, send mail using sendRawEmail method
        //current sendBulkTemplatedEmail method doesn't support attachments
        if (!empty($message->getAttachments())) {
            return $this->sendRawEmail($message, $evt, $failedRecipients);
        }

        list($amazonTemplate, $amazonMessage) = $this->getAmazonSesMessage($message);
        $amazonTemplateName                   = $amazonTemplate['TemplateName'];
        try {
            $client = $this->start();

            $promise = $client->createTemplateAsync([
                'Template' => $amazonTemplate,
            ]);

            $promise->then(function () use ($client, $amazonMessage, $amazonTemplateName) {
                $client->sendBulkTemplatedEmailAsync($amazonMessage)->then(
                    function () use ($amazonTemplateName) {
                        $client->deleteTemplate([
                            'TemplateName' => $amazonTemplateName,
                        ])->wait();
                    }
                )->wait();
            });

            $promise->wait();

            if ($evt) {
                $evt->setResult(\Swift_Events_SendEvent::RESULT_SUCCESS);
                $evt->setFailedRecipients($failedRecipients);
                $this->getDispatcher()->dispatchEvent($evt, 'sendPerformed');
            }

            return $count;
        } catch (AwsException $ex) {
            $this->triggerSendError($evt, $failedRecipients);
            $message->generateId();

            $this->throwException($ex->getAwsErrorMessage());
        } catch (Exception $e) {
            $this->triggerSendError($evt, $failedRecipients);
            $message->generateId();

            $this->throwException($e->getMessage());
        }

        return $count;
    }

    /**
     * @param \Swift_Events_SendEvent $evt
     * @param array $failedRecipients
     */
    private function triggerSendError(\Swift_Events_SendEvent $evt, $failedRecipients)
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
     * @param \Swift_Mime_Message $message
     *
     * @return array
     */
    public function getAmazonSesMessage(\Swift_Mime_Message $message)
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
        if (count($messageArray['recipients']['cc']) > 0) {
            $BccAddresses = array_keys($messageArray['recipients']['bcc']);
        }

        //build amazon ses template array
        $template = [
            'TemplateName' => 'MauticTemplate'.$emailId.time(), //unique template name
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
        'DefaultTemplateData'  => $destinations[0]['ReplacementTemplateData'],
            'Destinations'     => $destinations,
            'ReplyToAddresses' => [$messageArray['replyTo']['email']],
            'Source'           => $messageArray['from']['email'],
            'Template'         => $template['TemplateName'],
        ];

        return [$template, $amazonMessage];
    }

    /**
     * @param \Swift_Mime_Message $message
     * @param \Swift_Events_SendEvent @evt
     * @param null                $failedRecipients
     *
     * @return array
     */
    public function sendRawEmail(\Swift_Mime_Message $message, \Swift_Events_SendEvent $evt, &$failedRecipients = null)
    {
        try {
            $client   = $this->start();
            $commands = [];
            foreach ($this->getAmazonMessage($message) as $rawEmail) {
                $commands[] = $client->getCommand('sendRawEmail', $rawEmail);
            }
            $pool = new CommandPool($client, $commands, [
                'concurrency' => $this->concurrency,
                'fulfilled'   => function (ResultInterface $result, $iteratorId) use ($commands, $evt) {
                    if ($evt) {
                        $evt->setResult(\Swift_Events_SendEvent::RESULT_SUCCESS);
                        $evt->setFailedRecipients($failedRecipients);
                        $this->getDispatcher()->dispatchEvent($evt, 'sendPerformed');
                    }
                },
                'rejected' => function (AwsException $reason, $iteratorId) use ($commands, $evt) {
                    $this->triggerSendError($evt, []);
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
     * @param \Swift_Mime_Message $message
     *
     * @return array
     */
    public function getAmazonMessage(\Swift_Mime_Message $message)
    {
        $this->message = $message;
        $metadata      = $this->getMetadata();
        $emailBody     = $this->message->getBody();

        if (!empty($metadata)) {
            $metadataSet  = reset($metadata);
            $tokens       = (!empty($metadataSet['tokens'])) ? $metadataSet['tokens'] : [];
            $mauticTokens = array_keys($tokens);
        }

        $CcAddresses = [];
        if (count($messageArray['recipients']['cc']) > 0) {
            $CcAddresses = array_keys($messageArray['recipients']['cc']);
        }

        $BccAddresses = [];
        if (count($messageArray['recipients']['cc']) > 0) {
            $BccAddresses = array_keys($messageArray['recipients']['bcc']);
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
        $message .= "Content-Type: multipart/mixed; boundary=\"$separator_multipart\"\n";
        $message .= "\n--$separator_multipart\n";

        $message .= "Content-Type: multipart/alternative; boundary=\"$separator\"\n";
        $message .= "\n--$separator\n";
        $message .= "Content-Type: text/html; charset=\"UTF-8\"\n";
        $message .= "\n".$msg['html']."\n";
        $message .= "\n--$separator--\n";

        foreach ($msg['attachments'] as $attachment) {
            $message .= "--$separator_multipart\n";
            $message .= 'Content-Type: '.$attachment['type'].'; name="'.$attachment['name']."\"\n";
            $message .= 'Content-Disposition: attachment; filename="'.$attachment['name']."\"\n";
            $message .= "Content-Transfer-Encoding: base64\n";
            $message .= ''.$attachment['content']."\n";
            $message .= "--$separator_multipart--";
        }

        return $message;
    }

    /**
     * @return int
     */
    public function getMaxBatchLimit()
    {
        return 50;
    }

    /**
     * Returns a "transport" string to match the URL path /mailer/{transport}/callback.
     *
     * @return string
     */
    public function getCallbackPath()
    {
        return 'amazon';
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
     * Handle bounces & complaints from Amazon.
     *
     * @param Request $request
     *
     * @return array
     */
    public function processCallbackRequest(Request $request)
    {
        $this->logger->debug('Receiving webhook from Amazon');

        $payload = json_decode($request->getContent(), true);

        return $this->processJsonPayload($payload);
    }

    /**
     * Process json request from Amazon SES.
     *
     * http://docs.aws.amazon.com/ses/latest/DeveloperGuide/best-practices-bounces-complaints.html
     *
     * @param array $payload from Amazon SES
     */
    public function processJsonPayload(array $payload)
    {
        if (!isset($payload['Type'])) {
            throw new HttpException(400, "Key 'Type' not found in payload ");
        }

        if ($payload['Type'] == 'SubscriptionConfirmation') {
            // Confirm Amazon SNS subscription by calling back the SubscribeURL from the playload
            try {
                $response = $this->httpClient->get($payload['SubscribeURL']);
                if ($response->code == 200) {
                    $this->logger->info('Callback to SubscribeURL from Amazon SNS successfully');

                    return;
                }

                $reason = 'HTTP Code '.$response->code.', '.$response->body;
            } catch (UnexpectedResponseException $e) {
                $reason = $e->getMessage();
            }

            $this->logger->error('Callback to SubscribeURL from Amazon SNS failed, reason: '.$reason);

            return;
        }

        if ($payload['Type'] == 'Notification') {
            $message = json_decode($payload['Message'], true);

            // only deal with hard bounces
            if ($message['notificationType'] == 'Bounce' && $message['bounce']['bounceType'] == 'Permanent') {
                // Get bounced recipients in an array
                $bouncedRecipients = $message['bounce']['bouncedRecipients'];
                foreach ($bouncedRecipients as $bouncedRecipient) {
                    $this->transportCallback->addFailureByAddress($bouncedRecipient['emailAddress'], $bouncedRecipient['diagnosticCode']);
                    $this->logger->debug("Mark email '".$bouncedRecipient['emailAddress']."' as bounced, reason: ".$bouncedRecipient['diagnosticCode']);
                }

                return;
            }

            // unsubscribe customer that complain about spam at their mail provider
            if ($message['notificationType'] == 'Complaint') {
                foreach ($message['complaint']['complainedRecipients'] as $complainedRecipient) {
                    $reason = null;
                    if (isset($message['complaint']['complaintFeedbackType'])) {
                        // http://docs.aws.amazon.com/ses/latest/DeveloperGuide/notification-contents.html#complaint-object
                        switch ($message['complaint']['complaintFeedbackType']) {
                            case 'abuse':
                                $reason = $this->translator->trans('mautic.email.complaint.reason.abuse');
                                break;
                            case 'fraud':
                                $reason = $this->translator->trans('mautic.email.complaint.reason.fraud');
                                break;
                            case 'virus':
                                $reason = $this->translator->trans('mautic.email.complaint.reason.virus');
                                break;
                        }
                    }

                    if ($reason == null) {
                        $reason = $this->translator->trans('mautic.email.complaint.reason.unknown');
                    }

                    $this->transportCallback->addFailureByAddress($complainedRecipient['emailAddress'], $reason, DoNotContact::UNSUBSCRIBED);

                    $this->logger->debug("Unsubscribe email '".$complainedRecipient['emailAddress']."'");
                }
            }
        }
    }

    /**
     * @param Message $message
     *
     * @throws BounceNotFound
     */
    public function processBounce(Message $message)
    {
        if (self::SNS_ADDRESS !== $message->fromAddress) {
            throw new BounceNotFound();
        }

        $message = $this->getSnsPayload($message->textPlain);
        if ('Bounce' !== $message['notificationType']) {
            throw new BounceNotFound();
        }

        $bounce = new BouncedEmail();
        $bounce->setContactEmail($message['bounce']['bouncedRecipients'][0]['emailAddress'])
            ->setBounceAddress($message['mail']['source'])
            ->setType(Type::UNKNOWN)
            ->setRuleCategory(Category::UNKNOWN)
            ->setRuleNumber('0013')
            ->setIsFinal(true);

        return $bounce;
    }

    /**
     * @param Message $message
     *
     * @return UnsubscribedEmail
     *
     * @throws UnsubscriptionNotFound
     */
    public function processUnsubscription(Message $message)
    {
        if (self::SNS_ADDRESS !== $message->fromAddress) {
            throw new UnsubscriptionNotFound();
        }

        $message = $this->getSnsPayload($message->textPlain);
        if ('Complaint' !== $message['notificationType']) {
            throw new UnsubscriptionNotFound();
        }

        return new UnsubscribedEmail($message['complaint']['complainedRecipients'][0]['emailAddress'], $message['mail']['source']);
    }

    /**
     * @param string $body
     *
     * @return array
     */
    protected function getSnsPayload($body)
    {
        return json_decode(strtok($body, "\n"), true);
    }
}
