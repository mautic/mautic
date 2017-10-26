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

use Aws\CommandInterface;
use Aws\CommandPool;
use Aws\Credentials\Credentials;
use Aws\Exception\AwsException;
use Aws\ResultInterface;
use Aws\Ses\SesClient;
use Joomla\Http\Exception\UnexpectedResponseException;
use Joomla\Http\Http;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\DoNotContact;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class AmazonTransport.
 */
class AmazonTransport extends AbstractTokenArrayTransport implements \Swift_Transport, InterfaceTokenTransport, InterfaceCallbackTransport
{
    private $httpClient;
    private $region;
    private $username;
    private $password;
    private $concurrency;

    /**
     * {@inheritdoc}
     */
    public function __construct(Http $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @return mixed
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
            'version' => 'latest',
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
            throw new \Exception('Your AWS SES quota is exceeded');
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
        
        list($amazonTemplate, $amazonMessage) = $this->getAmazonSesMessage($message);
        $amazonTemplateName = $amazonTemplate['TemplateName'];
        
        try {
            $client     = $this->start();
            
            $promise = $client->createTemplateAsync([
                'Template' => $amazonTemplate,
            ]);
            
            //handle email attachments  --to-do
            
            $promise->then(function () use ($client, $amazonMessage, $amazonTemplateName ) {
                $emailPrimise = $client->sendBulkTemplatedEmailAsync($amazonMessage);
                
                $emailPrimise->then(function () use ($amazonTemplateName) {
                    $client->deleteTemplate([
                        'TemplateName' => $amazonTemplateName,
                    ]);
                });
            });
            
            if ($evt) {
                $evt->setResult(\Swift_Events_SendEvent::RESULT_SUCCESS);
                $evt->setFailedRecipients($failedRecipients);
                $this->getDispatcher()->dispatchEvent($evt, 'sendPerformed');
            }
            
            //check bulk email failed emails  --to-do
                        
            return $count;
        } catch (AwsException $ex) {
            $this->triggerSendError($evt,$failedRecipients);
            $message->generateId();
            
            $this->throwException($ex->getAwsErrorMessage());
        } catch (Exception $e) {
            $this->triggerSendError($evt,$failedRecipients);
            $message->generateId();
            
            $this->throwException($e->getMessage());
        }
        
        return $count;
    }
    
    /**
     * @param type $evt
     * @param type $failedRecipients
     * 
     */
    private function triggerSendError($evt,$failedRecipients)
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

        $message->generateId();
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
        $messageArray = [];
                
        if (!empty($metadata)) {
            $metadataSet  = reset($metadata);
            $emailId = $metadataSet['emailId'];
            $tokens       = (!empty($metadataSet['tokens'])) ? $metadataSet['tokens'] : [];
            $mauticTokens = array_keys($tokens);
            $tokenReplace = $amazonTokens = [];
            foreach ($tokens as $search => $token) {
                $tokenKey = preg_replace('/[^\da-z]/i', '_', trim($search, '{}'));
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
            "TemplateName" => "MauticTemplate".$emailId.time(),//unique template name
            "SubjectPart" => $messageArray["subject"],
            "TextPart" => $messageArray["text"],
            "HtmlPart" => $messageArray["html"]
        ];
        
        $destinations = [];
        foreach ($metadata as $recipient => $mailData) {
            
            $ReplacementTemplateData = [];
            foreach ( $mailData['tokens'] as $token => $tokenData) {
                $ReplacementTemplateData[$amazonTokens[$token]] = $tokenData;
            }
            
            $destinations[] = [
                'Destination' => [
                    'BccAddresses' => $BccAddresses,
                    'CcAddresses' => $CcAddresses,
                    'ToAddresses' => [$recipient],
                ],
                'ReplacementTemplateData' => $ReplacementTemplateData,
            ];
        }
        
        //build amazon ses message array
        $amazonMessage = [
            'ConfigurationSetName' => 'ConfigSet',
            'Destinations' => $destinations,
            'ReplyToAddresses' => $messageArray['replyTo']['email'],
            'Source' => $messageArray['from']['email'],
            'Template' => $template['TemplateName'],
        ];
        
        return array($template, $amazonMessage);
    }

    /**
     * @return int
     */
    public function getMaxBatchLimit()
    {
        return 5000;
    }

    /**
     * Returns a "transport" string to match the URL path /mailer/{transport}/callback.
     *
     * @return mixed
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
     * @param Request       $request
     * @param MauticFactory $factory
     *
     * @return mixed
     */
    public function handleCallbackResponse(Request $request, MauticFactory $factory)
    {
        $translator = $factory->getTranslator();
        $logger     = $factory->getLogger();
        $logger->debug('Receiving webHook from Amazon');

        $payload = json_decode($request->getContent(), true);

        return $this->processJsonPayload($payload, $logger, $translator);
    }

    /**
     * Process json request from Amazon SES.
     *
     * http://docs.aws.amazon.com/ses/latest/DeveloperGuide/best-practices-bounces-complaints.html
     *
     * @param array $payload from Amazon SES
     * @param $logger
     * @param $translator
     *
     * @return array with bounced and unsubscribed email addresses
     */
    public function processJsonPayload(array $payload, $logger, $translator)
    {
        // Data structure that Mautic expects to be returned from this callback
        $rows = [
            DoNotContact::BOUNCED => [
                'hashIds' => [],
                'emails'  => [],
            ],
            DoNotContact::UNSUBSCRIBED => [
                'hashIds' => [],
                'emails'  => [],
            ],
        ];

        if (!isset($payload['Type'])) {
            throw new HttpException(400, "Key 'Type' not found in payload ");
        }

        if ($payload['Type'] == 'SubscriptionConfirmation') {
            // Confirm Amazon SNS subscription by calling back the SubscribeURL from the playload
            $requestFailed = false;
            try {
                $response = $this->httpClient->get($payload['SubscribeURL']);
                if ($response->code == 200) {
                    $logger->info('Callback to SubscribeURL from Amazon SNS successfully');
                } else {
                    $requestFailed = true;
                    $reason        = 'HTTP Code '.$response->code.', '.$response->body;
                }
            } catch (UnexpectedResponseException $e) {
                $requestFailed = true;
                $reason        = $e->getMessage();
            }

            if ($requestFailed) {
                $logger->error('Callback to SubscribeURL from Amazon SNS failed, reason: '.$reason);
            }
        } elseif ($payload['Type'] == 'Notification') {
            $message = json_decode($payload['Message'], true);

            // only deal with hard bounces
            if ($message['notificationType'] == 'Bounce' && $message['bounce']['bounceType'] == 'Permanent') {
                // Get bounced recipients in an array
                $bouncedRecipients = $message['bounce']['bouncedRecipients'];
                foreach ($bouncedRecipients as $bouncedRecipient) {
                    $rows[DoNotContact::BOUNCED]['emails'][$bouncedRecipient['emailAddress']] = $bouncedRecipient['diagnosticCode'];
                    $logger->debug("Mark email '".$bouncedRecipient['emailAddress']."' as bounced, reason: ".$bouncedRecipient['diagnosticCode']);
                }
            }
            // unsubscribe customer that complain about spam at their mail provider
            elseif ($message['notificationType'] == 'Complaint') {
                foreach ($message['complaint']['complainedRecipients'] as $complainedRecipient) {
                    $reason = null;
                    if (isset($message['complaint']['complaintFeedbackType'])) {
                        // http://docs.aws.amazon.com/ses/latest/DeveloperGuide/notification-contents.html#complaint-object
                        switch ($message['complaint']['complaintFeedbackType']) {
                            case 'abuse':
                                $reason = $translator->trans('mautic.email.complaint.reason.abuse');
                                break;
                            case 'fraud':
                                $reason = $translator->trans('mautic.email.complaint.reason.fraud');
                                break;
                            case 'virus':
                                $reason = $translator->trans('mautic.email.complaint.reason.virus');
                                break;
                        }
                    }

                    if ($reason == null) {
                        $reason = $translator->trans('mautic.email.complaint.reason.unknown');
                    }

                    $rows[DoNotContact::UNSUBSCRIBED]['emails'][$complainedRecipient['emailAddress']] = $reason;
                    $logger->debug("Unsubscribe email '".$complainedRecipient['emailAddress']."'");
                }
            }
        }

        return $rows;
    }
}
