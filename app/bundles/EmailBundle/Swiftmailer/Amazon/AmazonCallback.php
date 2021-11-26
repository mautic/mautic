<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Amazon;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
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

class AmazonCallback
{
    /**
     * From address for SNS email.
     */
    const SNS_ADDRESS = 'no-reply@sns.amazonaws.com';

    private TranslatorInterface $translator;
    private LoggerInterface $logger;
    private Client $httpClient;
    private TransportCallback $transportCallback;

    public function __construct(TranslatorInterface $translator, LoggerInterface $logger, Client $httpClient, TransportCallback $transportCallback)
    {
        $this->translator        = $translator;
        $this->logger            = $logger;
        $this->transportCallback = $transportCallback;
        $this->httpClient        = $httpClient;
    }

    /**
     * Handle bounces & complaints from Amazon.
     *
     * @return array
     */
    public function processCallbackRequest(Request $request)
    {
        $this->logger->debug('Receiving webhook from Amazon');

        $payload = json_decode($request->getContent(), true);

        if (0 !== json_last_error()) {
            throw new HttpException(400, 'AmazonCallback: Invalid JSON Payload');
        }

        if (!isset($payload['Type']) && !isset($payload['eventType'])) {
            throw new HttpException(400, "Key 'Type' not found in payload ");
        }

        // determine correct key for message type (global or via ConfigurationSet)
        $type = (array_key_exists('Type', $payload) ? $payload['Type'] : $payload['eventType']);

        return $this->processJsonPayload($payload, $type);
    }

    /**
     * Process json request from Amazon SES.
     *
     * http://docs.aws.amazon.com/ses/latest/DeveloperGuide/best-practices-bounces-complaints.html
     *
     * @param array $payload from Amazon SES
     */
    public function processJsonPayload(array $payload, $type)
    {
        switch ($type) {
            case 'SubscriptionConfirmation':
                    // Confirm Amazon SNS subscription by calling back the SubscribeURL from the playload
                    try {
                        $response = $this->httpClient->get($payload['SubscribeURL']);
                        if (200 == $response->getStatusCode()) {
                            $this->logger->info('Callback to SubscribeURL from Amazon SNS successfully');
                            break;
                        }

                        $reason = 'HTTP Code '.$response->getStatusCode().', '.$response->getBody();
                    } catch (TransferException $e) {
                        $reason = $e->getMessage();
                    }

                    $this->logger->error('Callback to SubscribeURL from Amazon SNS failed, reason: '.$reason);
            break;
            case 'Notification':
                $message = json_decode($payload['Message'], true);

                $this->processJsonPayload($message, $message['notificationType']);
            break;
            case 'Complaint':
                foreach ($payload['complaint']['complainedRecipients'] as $complainedRecipient) {
                    $reason = null;
                    if (isset($payload['complaint']['complaintFeedbackType'])) {
                        // http://docs.aws.amazon.com/ses/latest/DeveloperGuide/notification-contents.html#complaint-object
                        switch ($payload['complaint']['complaintFeedbackType']) {
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

                    if (null == $reason) {
                        $reason = $this->translator->trans('mautic.email.complaint.reason.unknown');
                    }

                    $this->transportCallback->addFailureByAddress($complainedRecipient['emailAddress'], $reason, DoNotContact::UNSUBSCRIBED);

                    $this->logger->debug("Unsubscribe email '".$complainedRecipient['emailAddress']."'");
                }

            break;
            case 'Bounce':
                if ('Permanent' == $payload['bounce']['bounceType']) {
                    $emailId = null;

                    if (isset($payload['mail']['headers'])) {
                        foreach ($payload['mail']['headers'] as $header) {
                            if ('X-EMAIL-ID' === $header['name']) {
                                $emailId = $header['value'];
                            }
                        }
                    }

                    // Get bounced recipients in an array
                    $bouncedRecipients = $payload['bounce']['bouncedRecipients'];
                    foreach ($bouncedRecipients as $bouncedRecipient) {
                        $bounceCode = array_key_exists('diagnosticCode', $bouncedRecipient) ? $bouncedRecipient['diagnosticCode'] : 'unknown';
                        $this->transportCallback->addFailureByAddress($bouncedRecipient['emailAddress'], $bounceCode, DoNotContact::BOUNCED, $emailId);
                        $this->logger->debug("Mark email '".$bouncedRecipient['emailAddress']."' as bounced, reason: ".$bounceCode);
                    }
                }
            break;
            default:
                $this->logger->warning("Received SES webhook of type '$payload[Type]' but couldn't understand payload");
                $this->logger->debug('SES webhook payload: '.json_encode($payload));
            break;
        }
    }

    /**
     * @throws BounceNotFound
     */
    public function processBounce(Message $message)
    {
        if (self::SNS_ADDRESS !== $message->fromAddress) {
            throw new BounceNotFound();
        }

        $message = $this->getSnsPayload($message->textPlain);
        $typeKey = (array_key_exists('eventType', $message) ? 'eventType' : 'notificationType');
        if ('Bounce' !== $message[$typeKey]) {
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
        $typeKey = (array_key_exists('eventType', $message) ? 'eventType' : 'notificationType');
        if ('Complaint' !== $message[$typeKey]) {
            throw new UnsubscriptionNotFound();
        }

        return new UnsubscribedEmail($message['complaint']['complainedRecipients'][0]['emailAddress'], $message['mail']['source']);
    }

    /**
     * @param string $body
     *
     * @return array
     */
    public function getSnsPayload($body)
    {
        return json_decode(strtok($body, "\n"), true);
    }
}
