<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Model;

use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Exception\FailedToSendToContactException;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Stat\Exception\StatNotFoundException;
use Mautic\EmailBundle\Stat\Reference;
use Mautic\EmailBundle\Stat\StatHelper;
use Mautic\EmailBundle\Swiftmailer\Exception\BatchQueueMaxException;
use Mautic\LeadBundle\Entity\DoNotContact as DNC;
use Mautic\LeadBundle\Model\DoNotContact;
use Symfony\Component\Translation\TranslatorInterface;

class SendEmailToContact
{
    /**
     * @var MailHelper
     */
    private $mailer;

    /**
     * @var StatHelper
     */
    private $statHelper;

    /**
     * @var DoNotContact
     */
    private $dncModel;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var null|string
     */
    private $singleEmailMode = null;

    /**
     * @var Stat[]
     */
    private $statEntities = [];

    /**
     * @var Stat[]
     */
    private $saveEntities = [];

    /**
     * @var Stat[]
     */
    private $deleteEntities = [];

    /**
     * @var array
     */
    private $failedContacts = [];

    /**
     * @var array
     */
    private $errorMessages = [];

    /**
     * @var array
     */
    private $badEmails = [];

    /**
     * @var array
     */
    private $emailSentCounts = [];

    /**
     * @var
     */
    private $emailEntityErrors;

    /**
     * @var null|int
     */
    private $emailEntityId;

    /**
     * @var null|int
     */
    private $listId;

    /**
     * @var int
     */
    private $statBatchCounter = 0;

    /**
     * @var array
     */
    private $contact = [];

    /**
     * SendEmailToContact constructor.
     *
     * @param MailHelper          $mailer
     * @param StatRepository      $statRepository
     * @param DoNotContact        $dncModel
     * @param TranslatorInterface $translator
     */
    public function __construct(MailHelper $mailer, StatHelper $statHelper, DoNotContact $dncModel, TranslatorInterface $translator)
    {
        $this->mailer     = $mailer;
        $this->statHelper = $statHelper;
        $this->dncModel   = $dncModel;
        $this->translator = $translator;
    }

    /**
     * @param bool $resetMailer
     *
     * @return $this
     */
    public function flush($resetMailer = true)
    {
        // Flushes the batch in case of using API mailers
        if ($this->emailEntityId && !$flushResult = $this->mailer->flushQueue()) {
            $sendFailures = $this->mailer->getErrors();

            // Check to see if failed recipients were stored by the transport
            if (!empty($sendFailures['failures'])) {
                $this->processSendFailures($sendFailures);
            } elseif ($this->singleEmailMode) {
                $this->errorMessages[$this->singleEmailMode] = implode('; ', $sendFailures);
            }
        }

        if ($resetMailer) {
            $this->mailer->reset(true);
        }

        return $this;
    }

    /**
     * Flush any remaining queued contacts, process spending stats, create DNC entries and reset this class.
     */
    public function finalFlush()
    {
        $this->flush();
        $this->statHelper->deletePending();
        $this->statHelper->reset();

        $this->processBadEmails();
    }

    /**
     * Use an Email entity to populate content, from, etc.
     *
     * @param Email $email
     * @param array $channel          ['channelName', 'channelId']
     * @param array $assetAttachments
     * @param array $slots            @deprecated to be removed in 3.0; support for old email template format
     *
     * @return $this
     */
    public function setEmail(Email $email, array $channel = [], array $customHeaders = [], array $assetAttachments = [], array $slots = [])
    {
        // Flush anything that's pending from a previous email
        $this->flush();

        // Enable the queue if applicable to the transport
        $this->mailer->enableQueue();

        if ($this->mailer->setEmail($email, true, $slots, $assetAttachments)) {
            $this->mailer->setSource($channel);
            $this->mailer->setCustomHeaders($customHeaders);

            // Note that the entity is set so that addContact does not generate errors
            $this->emailEntityId = $email->getId();
        } else {
            // Fail all the contacts in this batch
            $this->emailEntityErrors = $this->mailer->getErrors();
            $this->emailEntityId     = null;
        }

        return $this;
    }

    /**
     * @param null|int $id
     *
     * @return $this
     */
    public function setListId($id)
    {
        $this->listId = empty($id) ? null : (int) $id;

        return $this;
    }

    /**
     * @param array $contact
     * @param array $tokens
     *
     * @return $this
     *
     * @throws FailedToSendToContactException
     */
    public function setContact(array $contact, array $tokens = [])
    {
        $this->contact = $contact;

        if (!$this->emailEntityId) {
            // There was an error configuring the email so auto fail
            $this->failContact(false, $this->emailEntityErrors);
        }

        $this->mailer->setTokens($tokens);
        $this->mailer->setLead($contact);
        $this->mailer->setIdHash(); //auto generates

        try {
            if (!$this->mailer->addTo($contact['email'], $contact['firstname'].' '.$contact['lastname'])) {
                $this->failContact();
            }
        } catch (BatchQueueMaxException $e) {
            // Queue full so flush then try again
            $this->flush(false);

            if (!$this->mailer->addTo($contact['email'], $contact['firstname'].' '.$contact['lastname'])) {
                $this->failContact();
            }
        }

        return $this;
    }

    /**
     * @throws FailedToSendToContactException
     */
    public function send()
    {
        if ($this->mailer->inTokenizationMode()) {
            list($success, $errors) = $this->queueTokenizedEmail();
        } else {
            list($success, $errors) = $this->sendStandardEmail();
        }

        //queue or send the message
        if (!$success) {
            unset($errors['failures']);
            $this->failContact(false, implode('; ', (array) $errors));
        }
    }

    /**
     * Reset everything.
     */
    public function reset()
    {
        $this->saveEntities      = [];
        $this->deleteEntities    = [];
        $this->statEntities      = [];
        $this->badEmails         = [];
        $this->errorMessages     = [];
        $this->failedContacts    = [];
        $this->emailEntityErrors = null;
        $this->emailEntityId     = null;
        $this->emailSentCounts   = [];
        $this->singleEmailMode   = null;
        $this->listId            = null;
        $this->statBatchCounter  = 0;
        $this->contact           = [];

        $this->dncModel->clearEntities();

        $this->mailer->reset();
    }

    /**
     * @return array
     */
    public function getSentCounts()
    {
        return $this->emailSentCounts;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errorMessages;
    }

    /**
     * @return array
     */
    public function getFailedContacts()
    {
        return $this->failedContacts;
    }

    /**
     * @param bool   $hasBadEmail
     * @param string $errorMessages
     *
     * @throws FailedToSendToContactException
     */
    protected function failContact($hasBadEmail = true, $errorMessages = null)
    {
        if (null === $errorMessages) {
            // Clear the errors so it doesn't stop the next send
            $errorMessages = implode('; ', (array) $this->mailer->getErrors());
        }

        $this->errorMessages[$this->contact['id']]  = $errorMessages;
        $this->failedContacts[$this->contact['id']] = $this->contact['email'];

        try {
            $stat = $this->statHelper->getStat($this->contact['email']);
            $this->downEmailSentCount($stat->getEmailId());
            $this->statHelper->markForDeletion($stat);
        } catch (StatNotFoundException $exception) {
        }

        if ($hasBadEmail) {
            $this->badEmails[$this->contact['id']] = $this->contact['email'];
        }

        throw new FailedToSendToContactException($errorMessages);
    }

    /**
     * @param $sendFailures
     */
    protected function processSendFailures($sendFailures)
    {
        $failedEmailAddresses = $sendFailures['failures'];
        unset($sendFailures['failures']);
        $error = implode('; ', $sendFailures);

        // Delete the stat
        foreach ($failedEmailAddresses as $failedEmail) {
            try {
                /** @var Reference $stat */
                $stat = $this->statHelper->getStat($failedEmail);
            } catch (StatNotFoundException $exception) {
                continue;
            }

            // Add lead ID to list of failures
            $this->failedContacts[$stat->getEmailId()] = $failedEmail;
            $this->errorMessages[$stat->getLeadId()]   = $error;

            $this->statHelper->markForDeletion($stat);

            // Down sent counts
            $this->downEmailSentCount($stat->getEmailId());
        }
    }

    /**
     * Add DNC entries for bad emails to get them out of the queue permanently.
     */
    protected function processBadEmails()
    {
        // Update bad emails as bounces
        if (count($this->badEmails)) {
            foreach ($this->badEmails as $contactId => $contactEmail) {
                $this->dncModel->addDncForContact(
                    $contactId,
                    ['email' => $this->emailEntityId],
                    DNC::BOUNCED,
                    $this->translator->trans('mautic.email.bounce.reason.bad_email'),
                    true,
                    false
                );
            }
        }
    }

    /**
     * @param $email
     */
    protected function createContactStatEntry($email)
    {
        ++$this->statBatchCounter;

        $stat = $this->mailer->createEmailStat(false, null, $this->listId);

        // Store it in the statEntities array so that the stat can be deleted if the transport fails the
        // send for whatever reason after flushing the queue
        $this->statHelper->storeStat($stat, $email);

        $this->upEmailSentCount($stat->getEmail()->getId());
    }

    /**
     * Up sent counter for the given email ID.
     */
    protected function upEmailSentCount($emailId)
    {
        // Up sent counts
        if (!isset($this->emailSentCounts[$emailId])) {
            $this->emailSentCounts[$emailId] = 0;
        }

        ++$this->emailSentCounts[$emailId];
    }

    /**
     * Down sent counter for the given email ID.
     */
    protected function downEmailSentCount($emailId)
    {
        --$this->emailSentCounts[$emailId];
    }

    /**
     * @return array
     */
    protected function queueTokenizedEmail()
    {
        list($queued, $queueErrors) = $this->mailer->queue(true, MailHelper::QUEUE_RETURN_ERRORS);

        if ($queued) {
            // Create stat first to ensure it is available for emails sent immediately
            $this->createContactStatEntry($this->contact['email']);
        }

        return [$queued, $queueErrors];
    }

    /**
     * @return array
     */
    protected function sendStandardEmail()
    {
        // Dispatch the event to generate the tokens
        $this->mailer->dispatchSendEvent();

        // Create the stat to ensure it is availble for emails sent
        $this->createContactStatEntry($this->contact['email']);

        // Now send but don't redispatch the event
        return $this->mailer->queue(true, MailHelper::QUEUE_RETURN_ERRORS);
    }
}
