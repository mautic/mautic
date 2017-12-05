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
use Mautic\EmailBundle\MonitoredEmail\Exception\BounceNotFound;
use Mautic\EmailBundle\MonitoredEmail\Exception\UnsubscriptionNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\BouncedEmail;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition\Category;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition\Type;
use Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription\UnsubscribedEmail;
use Mautic\LeadBundle\Entity\DoNotContact;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class AmazonTransport.
 */
class AmazonTransport extends \Swift_SmtpTransport implements InterfaceCallbackTransport, BounceProcessorInterface, UnsubscriptionProcessorInterface
{
    /**
     * From address for SNS email.
     */
    const SNS_ADDRESS = 'no-reply@sns.amazonaws.com';

    /**
     * @var Http
     */
    private $httpClient;

    /**
     * {@inheritdoc}
     */
    public function __construct($host, Http $httpClient)
    {
        parent::__construct($host, 2587, 'tls');
        $this->setAuthMode('login');
        $this->httpClient = $httpClient;
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
     * Handle bounces & complaints from Amazon.
     *
     * @param Request       $request
     * @param MauticFactory $factory
     *
     * @return array
     */
    public function handleCallbackResponse(Request $request, MauticFactory $factory)
    {
        $translator = $factory->getTranslator();
        $logger     = $factory->getLogger();
        $logger->debug('Receiving webhook from Amazon');

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
