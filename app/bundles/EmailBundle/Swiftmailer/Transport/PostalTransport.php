<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Transport;

use Mautic\EmailBundle\Model\TransportCallback;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class PostalTransport.
 */
class PostalTransport extends \Swift_SmtpTransport implements CallbackTransportInterface
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

    /**
     * PostalTransport constructor.
     */
    public function __construct(TranslatorInterface $translator, LoggerInterface $logger, TransportCallback $transportCallback, $host = 'localhost', $port = 25, $security = 'tls')
    {
        $this->translator        = $translator;
        $this->logger            = $logger;
        $this->transportCallback = $transportCallback;

        parent::__construct($host, $port, $security);

        $this->setAuthMode('login');
    }

    /**
     * Returns a "transport" string to match the URL path /mailer/{transport}/callback.
     *
     * @return mixed
     */
    public function getCallbackPath()
    {
        return 'postal';
    }

    /**
     * Handle bounces & complaints from Postal.
     */
    public function processCallbackRequest(Request $request)
    {
        $this->logger->debug('Receiving webhook from Postal: ');

        $postData = json_decode($request->getContent(), true);

        $event    = $postData['event'];
        $payload  = $postData['payload'];
        $message  = isset($payload['original_message']) ? $payload['original_message'] : $payload['message'];
        $email    = $message['to'];

        if ('MessageDeliveryFailed' == $event) {
            $this->transportCallback->addFailureByAddress($email, $this->translator->trans('mautic.email.bounce.reason.other'));
        } elseif ('MessageBounced' == $event) {
            $this->transportCallback->addFailureByAddress($email, $this->translator->trans('mautic.email.bounce.reason.hard_bounce'));
        }
    }
}
