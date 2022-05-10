<?php

namespace Mautic\EmailBundle\Swiftmailer\Transport;

use Mautic\EmailBundle\Model\TransportCallback;
use Mautic\LeadBundle\Entity\DoNotContact;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class PepipostTransport.
 */
class PepipostTransport extends \Swift_SmtpTransport implements CallbackTransportInterface
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
     * PepipostTransport constructor.
     */
    public function __construct(TranslatorInterface $translator, LoggerInterface $logger, TransportCallback $transportCallback)
    {
        $this->translator        = $translator;
        $this->logger            = $logger;
        $this->transportCallback = $transportCallback;

        parent::__construct('smtp.pepipost.com', 587, 'tls');

        $this->setAuthMode('login');
    }

    /**
     * Returns a "transport" string to match the URL path /mailer/{transport}/callback.
     *
     * @return mixed
     */
    public function getCallbackPath()
    {
        return 'pepipost';
    }

    /**
     * Handle bounces & complaints from Pepipost.
     */
    public function processCallbackRequest(Request $request)
    {
        $this->logger->debug('Receiving webhook from Pepipost');

        $email    = rawurldecode($request->get(0)['EMAIL']);
        $event    = rawurldecode($request->get(0)['EVENT']);

        if ('unsubscribed' == $event) {
            $this->transportCallback->addFailureByAddress($email, $this->translator->trans('mautic.email.bounce.reason.unsubscribed'), DoNotContact::UNSUBSCRIBED);
        } elseif ('invalid' == $event) {
            $this->transportCallback->addFailureByAddress($email, $this->translator->trans('mautic.email.bounce.reason.invalid'));
        } elseif ('bounced' == $event) {
            $type = rawurldecode($request->get(0)['BOUNCE_TYPE']);
            if ('HARDBOUNCE' == $type) {
                $this->transportCallback->addFailureByAddress($email, $this->translator->trans('mautic.email.bounce.reason.hard_bounce'));
            } elseif ('SOFTBOUNCE' == $type) {
                $this->transportCallback->addFailureByAddress($email, $this->translator->trans('mautic.email.bounce.reason.soft_bounce'));
            }
        } elseif ('spam' == $event) {
            $this->transportCallback->addFailureByAddress($email, $this->translator->trans('mautic.email.bounce.reason.spam'));
        } elseif ('dropped' == $event) {
            $this->transportCallback->addFailureByAddress($email, $this->translator->trans('mautic.email.bounce.reason.dropped'));
        }
    }
}
