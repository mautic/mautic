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

use Joomla\Http\Exception\UnexpectedResponseException;
use Joomla\Http\Http;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\DoNotContact;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Mautic\EmailBundle\Helper\MailHelper;
use Aws\Ses\SesClient;
use bandwidthThrottle\tokenBucket\Rate;
use bandwidthThrottle\tokenBucket\TokenBucket;
use bandwidthThrottle\tokenBucket\BlockingConsumer;
use bandwidthThrottle\tokenBucket\storage\FileStorage;

/**
 * Class AmazonTransport.
 */
class AmazonTransport extends AbstractTokenHttpTransport implements InterfaceCallbackTransport
{
    private $httpClient;

    /* The amazon region on where your current host stays */
    private $region;

    /* Email quota of remaining messages that can be sent */
    private $emailQuoteRemaining = 0;

    /* Max emails per second according to your account quota */
    private $maxSendRate = 0;

    /* @var $_consumer BlockingConsumer */
    private $consumer;

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
     * SES authorization and choice of region
     * Initializing of TokenBucket.
     *
     * @return SesClient
     *
     * @throws \Exception
     */
    public function getApiEndpoint()
    {
        $client = SesClient::factory(array(
            'region' => $this->getRegion(),
            'key' => $this->getUsername(),
            'secret' => $this->getPassword(),
        ));

        /**
         * AWS SES has a limit of how many messages can be sent in a 24h time slot. The remaining messages are calculated
         * from the api. The transport will fail when the quota is exceeded.
         */
        $quota = $client->getSendQuota();
        $this->emailQuoteRemaining = $quota->get('Max24HourSend') - $quota->get('SentLast24Hours');
        if ($this->emailQuoteRemaining > 0) {
            $this->started = true;
        } else {
            throw new \Exception('Your AWS SES quota is exceeded');
        }

        /*
         * AWS SES limits the amount of messages that can be sent in parallel. This limit is bound to an account and can vary.
         * The AmazonTransport reads that value from the API  and uses a TokenBucket to limit the requests to that rate
         */
        $this->maxSendRate = floor($quota->get('MaxSendRate'));
        // Initialize a token bucket to track the sending limit
        $storage = new FileStorage(tempnam(sys_get_temp_dir(), 'MauticBucket'));
        $rate = new Rate($this->maxSendRate, Rate::SECOND);
        $bucket = new TokenBucket($this->maxSendRate, $rate, $storage);
        $this->consumer = new BlockingConsumer($bucket);
        $bucket->bootstrap($this->maxSendRate);

        return $client;
    }

    /**
     * Returns the to AWS SES formatted Email.
     *
     * @return array $payload
     */
    public function getPayload()
    {
        /*Email formatting for the amazon SES API*/
        $payload = array(
            'Source' => current(array_keys($this->message->getFrom())),
            'Destination' => array(),
            'Message' => array(
                'Subject' => array(
                    'Data' => $this->message->getSubject(),
                    'Charset' => 'UTF-8',
                ),
                'Body' => array(
                    'Text' => array(
                        'Data' => MailHelper::getPlainTextFromMessage($this->message),
                        'Charset' => 'UTF-8',
                    ),
                    'Html' => array(
                        'Data' => $this->message->getBody(),
                        'Charset' => 'UTF-8',
                    ),
                ),
            ),
            'ReturnPath' => current(array_keys($this->message->getFrom())),
        );

        $to = $this->message->getTo();
        foreach ($to as $email => $name) {
            $payload['Destination']['ToAddresses'][] = $email;
        }
        $cc = $this->message->getCc();
        if (!empty($cc)) {
            foreach ($cc as $email => $name) {
                $payload['Destination']['CcAddresses'][] = $email;
            }
        }
        $bcc = $this->message->getBcc();
        if (!empty($bcc)) {
            foreach ($bcc as $email => $name) {
                $payload['Destination']['BccAddresses'][] = $email;
            }
        }

        return $payload;
    }

    /**
     * Try sending the Email with TokenBucket and through AWS SES.
     *
     * @param array $settings
     *
     * @return array
     *
     * @throws \Exception
     */
    public function post($settings = array())
    {
        $payload = empty($settings['payload']) ? $this->getPayload() : $settings['payload'];
        $client = empty($settings['client']) ? $this->getApiEndpoint() : $settings['client'];

        try {
            $this->consumer->consume(1);
            $client->sendEmail($payload);
            --$this->emailQuoteRemaining;
        } catch (\Exception $e) {
            throw new \Exception('The Email was not send! '.$e->getMessage());
        }

        return array();
    }

    public function getHeaders()
    {
        // Not used in Amazon SES.
    }

    /**
     * @return int
     */
    public function getMaxBatchLimit()
    {
        return 50;
    }

    public function handlePostResponse($response, $curlInfo)
    {
        // Implemented with function post().
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
        return 0;
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
        $logger = $factory->getLogger();
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
                'emails' => [],
            ],
            DoNotContact::UNSUBSCRIBED => [
                'hashIds' => [],
                'emails' => [],
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
                    $reason = 'HTTP Code '.$response->code.', '.$response->body;
                }
            } catch (UnexpectedResponseException $e) {
                $requestFailed = true;
                $reason = $e->getMessage();
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
