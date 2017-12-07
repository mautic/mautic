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

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\DoNotContact;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class MailjetlTransport.
 */
class MailjetTransport extends \Swift_SmtpTransport implements InterfaceCallbackTransport
{
    private $sandboxMode;

    private $sandboxMail;

    /**
     * {@inheritdoc}
     */
    public function __construct($host = 'localhost', $port = 25, $security = null, $sandboxMode = false, $sandboxMail = '')
    {
        parent::__construct('in-v3.mailjet.com', 587, 'tls');
        $this->setAuthMode('login');

        $this->setSandboxMode($sandboxMode);
        $this->setSandboxMail($sandboxMail);
    }

    public function send(\Swift_Mime_Message $message, &$failedRecipients = null)
    {
        // add leadIdHash to track this email
        if (isset($message->leadIdHash)) {
            // contact leadidHeash and email to be sure not applying email stat to bcc
            $message->getHeaders()->addTextHeader('X-MJ-CUSTOMID', $message->leadIdHash.'-'.key($message->getTo()));
        }

        if ($this->isSandboxMode()) {
            $message->setSubject(key($message->getTo()).' - '.$message->getSubject());
            $message->setTo($this->getSandboxMail());
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
        return 'mailjet';
    }

    /**
     * Handle response.
     *
     * @param Request       $request
     * @param MauticFactory $factory
     *
     * @return mixed
     */
    public function handleCallbackResponse(Request $request, MauticFactory $factory)
    {
        $postData = json_decode($request->getContent(), true);
        $rows     = [
            DoNotContact::BOUNCED => [
                'hashIds' => [],
                'emails'  => [],
            ],
            DoNotContact::UNSUBSCRIBED => [
                'hashIds' => [],
                'emails'  => [],
            ],
        ];

        if (is_array($postData) && isset($postData['event'])) {
            // Mailjet API callback V1
            $events = [
                $postData,
            ];
        } elseif (is_array($postData)) {
            // Mailjet API callback V2
            $events = $postData;
        } else {
            // respone must be an array
            return null;
        }

        foreach ($events as $event) {
            if (in_array($event['event'], [
                'bounce',
                'blocked',
                'spam',
                'unsub',
            ])) {
                if ($event['event'] === 'bounce' || $event['event'] === 'blocked') {
                    $reason = $event['error_related_to'].' : '.$event['error'];
                    $type   = DoNotContact::BOUNCED;
                } elseif ($event['event'] === 'spam') {
                    $reason = 'User reported email as spam, source :'.$event['source'];
                    $type   = DoNotContact::BOUNCED;
                } elseif ($event['event'] === 'unsub') {
                    $reason = 'User unsubscribed';
                    $type   = DoNotContact::UNSUBSCRIBED;
                }
                if (isset($event['CustomID']) && $event['CustomID'] !== '' && strpos($event['CustomID'], '-', 0) !== false) {
                    list($leadIdHash, $leadEmail) = explode('-', $event['CustomID']);
                    if ($event['email'] == $leadEmail) {
                        $rows[$type]['hashIds'][$leadIdHash] = $reason;
                    }
                } else {
                    $rows[$type]['emails'][$event['email']] = $reason;
                }
            }
        }

        return $rows;
    }

    /**
     * @return bool
     */
    private function isSandboxMode()
    {
        return $this->sandboxMode;
    }

    /**
     * @param bool $sandboxMode
     */
    private function setSandboxMode($sandboxMode)
    {
        $this->sandboxMode = $sandboxMode;
    }

    /**
     * @return string
     */
    private function getSandboxMail()
    {
        return $this->sandboxMail;
    }

    /**
     * @param string $sandboxMail
     */
    private function setSandboxMail($sandboxMail)
    {
        $this->sandboxMail = $sandboxMail;
    }
}
