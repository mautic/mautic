<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Amazon;

use Mautic\EmailBundle\Model\TransportCallback;
use Mautic\EmailBundle\MonitoredEmail\Exception\BounceNotFound;
use Mautic\EmailBundle\MonitoredEmail\Exception\UnsubscriptionNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\BouncedEmail;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition\Category;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition\Type;
use Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription\UnsubscribedEmail;
use Mautic\LeadBundle\Entity\DoNotContact;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class AmazonCallback
{

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TransportCallback
     */

    private $transportCallback;

    public function __construct(TranslatorInterface $translator, LoggerInterface $logger, TransportCallback $transportCallback)
    {
        $this->translator        = $translator;
        $this->logger            = $logger;
        $this->transportCallback = $transportCallback;
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

        if ('SubscriptionConfirmation' == $payload['Type']) {
            // Confirm Amazon SNS subscription by calling back the SubscribeURL from the playload
            try {
                $response = $this->httpClient->get($payload['SubscribeURL']);
                if (200 == $response->code) {
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

        if ('Notification' == $payload['Type']) {
            $message = json_decode($payload['Message'], true);

            // only deal with hard bounces
            if ('Bounce' == $message['notificationType'] && 'Permanent' == $message['bounce']['bounceType']) {
                $emailId = null;

                if (isset($message['mail']['headers'])) {
                    foreach ($message['mail']['headers'] as $header) {
                        if ('X-EMAIL-ID' === $header['name']) {
                            $emailId = $header['value'];
                        }
                    }
                }

                // Get bounced recipients in an array
                $bouncedRecipients = $message['bounce']['bouncedRecipients'];
                foreach ($bouncedRecipients as $bouncedRecipient) {
                    $bounceCode = array_key_exists('diagnosticCode', $bouncedRecipient) ? $bouncedRecipient['diagnosticCode'] : 'unknown';
                    $this->transportCallback->addFailureByAddress($bouncedRecipient['emailAddress'], $bounceCode, DoNotContact::BOUNCED, $emailId);
                    $this->logger->debug("Mark email '".$bouncedRecipient['emailAddress']."' as bounced, reason: ".$bounceCode);
                }

                return;
            }

            // unsubscribe customer that complain about spam at their mail provider
            if ('Complaint' == $message['notificationType']) {
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

                    if (null == $reason) {
                        $reason = $this->translator->trans('mautic.email.complaint.reason.unknown');
                    }

                    $this->transportCallback->addFailureByAddress($complainedRecipient['emailAddress'], $reason, DoNotContact::UNSUBSCRIBED);

                    $this->logger->debug("Unsubscribe email '".$complainedRecipient['emailAddress']."'");
                }

                return;
            }
        }

        $this->logger->warn("Received SES webhook of type '$payload[Type]' but couldn't understand payload");
        $this->logger->debug('SES webhook payload: '.json_encode($payload));
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
    public function getSnsPayload($body)
    {
        return json_decode(strtok($body, "\n"), true);
    }
}
