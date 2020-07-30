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

use Joomla\Http\Http;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\Swiftmailer\Amazon\AmazonCallback;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class AmazonTransport.
 */
class AmazonTransport extends \Swift_SmtpTransport implements CallbackTransportInterface, BounceProcessorInterface, UnsubscriptionProcessorInterface
{
    /**
     * From address for SNS email.
     */
    const SNS_ADDRESS = 'no-reply@sns.amazonaws.com';

    /**
     * @var AmazonCallback
     */
    private $amazonCallback;

    /**
     * AmazonTransport constructor.
     *
     * @param string $host
     */
    public function __construct($host, Http $httpClient, LoggerInterface $logger, TranslatorInterface $translator, AmazonCallback $amazonCallback)
    {
        parent::__construct($host, 2587, 'tls');
        $this->setAuthMode('login');
        $this->amazonCallback = $amazonCallback;
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
