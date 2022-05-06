<?php

namespace Mautic\EmailBundle\Swiftmailer\Transport;

use Mautic\EmailBundle\Model\TransportCallback;
use Mautic\LeadBundle\Entity\DoNotContact;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class ElasticEmailTransport.
 */
class ElasticemailTransport extends \Swift_SmtpTransport implements CallbackTransportInterface
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
     * ElasticemailTransport constructor.
     */
    public function __construct(TranslatorInterface $translator, LoggerInterface $logger, TransportCallback $transportCallback)
    {
        $this->translator        = $translator;
        $this->logger            = $logger;
        $this->transportCallback = $transportCallback;

        parent::__construct('smtp.elasticemail.com', 2525, null);

        $this->setAuthMode('login');
    }

    /**
     * @param null $failedRecipients
     *
     * @return int|void
     *
     * @throws \Exception
     */
    public function send(\Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        // IsTransactional header for all non bulk messages
        // https://elasticemail.com/support/guides/unsubscribe/
        if ('Bulk' != $message->getHeaders()->get('Precedence')) {
            $message->getHeaders()->addTextHeader('IsTransactional', 'True');
        }

        parent::send($message, $failedRecipients);
    }

    /**
     * Returns a "transport" string to match the URL path /mailer/{transport}/callback.
     *
     * @return mixed
     */
    public function getCallbackPath()
    {
        return 'elasticemail';
    }

    /**
     * Handle bounces & complaints from ElasticEmail.
     */
    public function processCallbackRequest(Request $request)
    {
        $this->logger->debug('Receiving webhook from ElasticEmail');

        $email    = rawurldecode($request->get('to'));
        $status   = rawurldecode($request->get('status'));
        $category = rawurldecode($request->get('category'));
        // https://elasticemail.com/support/delivery/http-web-notification
        if (in_array($status, ['AbuseReport', 'Unsubscribed']) || 'Spam' === $category) {
            $this->transportCallback->addFailureByAddress($email, $status, DoNotContact::UNSUBSCRIBED);
        } elseif (in_array($category, ['NotDelivered', 'NoMailbox', 'AccountProblem', 'DNSProblem', 'Unknown'])) {
            // just hard bounces https://elasticemail.com/support/user-interface/activity/bounced-category-filters
            $this->transportCallback->addFailureByAddress($email, $category);
        } elseif ('Error' == $status) {
            $this->transportCallback->addFailureByAddress($email, $this->translator->trans('mautic.email.complaint.reason.unknown'));
        }
    }
}
