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
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\DoNotContact;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ElasticEmailTransport.
 */
class ElasticemailTransport extends \Swift_SmtpTransport implements CallbackTransportInterface
{
    private $httpClient;

    /**
     * {@inheritdoc}
     */
    public function __construct($host = 'localhost', $port = 25, $security = null)
    {
        parent::__construct('smtp.elasticemail.com', 2525, null);

        $this->setAuthMode('login');
    }

    public function send(\Swift_Mime_Message $message, &$failedRecipients = null)
    {
        // IsTransactional header for all non bulk messages
        // https://elasticemail.com/support/guides/unsubscribe/
        if ($message->getHeaders()->get('Precedence') != 'Bulk') {
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
        $logger->debug('Receiving webhook from ElasticEmail');

        $rows     = [];
        $email    = rawurldecode($request->get('to'));
        $status   = rawurldecode($request->get('status'));
        $category = rawurldecode($request->get('category'));
        // https://elasticemail.com/support/delivery/http-web-notification
        if (in_array($status, ['AbuseReport', 'Unsubscribed'])) {
            $rows[DoNotContact::UNSUBSCRIBED]['emails'][$email] = $status;
        } elseif (in_array($category, ['NotDelivered', 'NoMailbox', 'AccountProblem', 'DNSProblem', 'Unknown', 'Spam'])) {
            // just hard bounces https://elasticemail.com/support/user-interface/activity/bounced-category-filters
            $rows[DoNotContact::BOUNCED]['emails'][$email] = $category;
        } elseif ($status == 'Error') {
            $rows[DoNotContact::BOUNCED]['emails'][$email] = $translator->trans('mautic.email.complaint.reason.unknown');
        }

        return $rows;
    }
}
