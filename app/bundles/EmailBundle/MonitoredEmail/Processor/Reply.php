<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\MonitoredEmail\Processor;

use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Search\ContactFinder;
use Mautic\LeadBundle\Model\LeadModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class Reply implements InterfaceProcessor
{
    /**
     * @var ContactFinder
     */
    protected $contactFinder;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Message
     */
    protected $message;

    /**
     * FeedBackLoop constructor.
     *
     * @param ContactFinder       $contactFinder
     * @param LeadModel           $leadModel
     * @param TranslatorInterface $translator
     * @param LoggerInterface     $logger
     */
    public function __construct(
        ContactFinder $contactFinder,
        LeadModel $leadModel,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        $this->contactFinder = $contactFinder;
        $this->leadModel     = $leadModel;
        $this->translator    = $translator;
        $this->logger        = $logger;
    }

    /**
     * @param $mailId
     * @param $refid
     */
    public function process(Message $message)
    {
        $this->logger->debug('Processing reply mail.');

        $mailIds = $this->mailbox->searchMailbox('TO '.$mailId);
        $mails   = $this->mailbox->getMailsInfo($mailIds);
        foreach ($mails as $mail) {
            if ($mail->message_id == $refid) {
                $this->checkMail($mail->uid);
            }
        }
    }

    /**
     * @param $mailUid
     *
     * @return bool
     */
    public function check($mailUid)
    {
        $mail = $this->mailbox->getMail($mailUid);
        if ($mail->returnPath && preg_match('#^(.*?)\+(.*?)@(.*?)$#', $mail->returnPath, $parts)) {
            if (strstr($parts[2], '_')) {
                // Has an ID hash so use it to find the lead
                list($ignore, $hashId) = explode('_', $parts[2]);
            }
        }
        if (empty($hashId) && preg_match('/email\/(.*?)\.gif/', $mail->textHtml, $parts)) {
            $hashId = $parts[1];
        }

        if (empty($hashId)) {
            $this->logger->debug('Could not find the email identifier(hashId).');

            return false;
        }
        $em = $this->factory->getEntityManager();
        // Search for the lead
        $statRepository = $em->getRepository('MauticEmailBundle:Stat');
        // Search by hashId
        $stat = $statRepository->findOneBy(['trackingHash' => $hashId]);
        if (!$stat) {
            $this->logger->debug('Could not find the replied email.');

            return false;
        }
        $this->logger->debug('Stat found with ID# '.$stat->getId());
        $this->leadModel->setCurrentLead($stat->getLead());
        $stat->setIsReplyed(1);
        $em->flush($stat);
        if ($this->dispatcher->hasListeners(EmailEvents::EMAIL_ON_REPLY)) {
            $event = new EmailReplyEvent($stat);
            $this->dispatcher->dispatch(EmailEvents::EMAIL_ON_REPLY, $event);
            unset($event);
        }

        return true;
    }
}
