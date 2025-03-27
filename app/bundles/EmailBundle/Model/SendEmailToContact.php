<?php

namespace Mautic\EmailBundle\Model;

use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Exception\FailedToSendToContactException;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Mailer\Exception\BatchQueueMaxException;
use Mautic\EmailBundle\Stat\Exception\StatNotFoundException;
use Mautic\EmailBundle\Stat\Reference;
use Mautic\EmailBundle\Stat\StatHelper;
use Mautic\LeadBundle\Entity\DoNotContact as DNC;
use Mautic\LeadBundle\Model\DoNotContact;
use Symfony\Contracts\Translation\TranslatorInterface;

class SendEmailToContact
{
    private array $failedContacts = [];

    private array $errorMessages = [];

    private array $badEmails = [];

    private array $emailSentCounts = [];

    /**
     * @var array|null
     */
    private $emailEntityErrors;

    /**
     * @var int|null
     */
    private $emailEntityId;

    private ?int $listId = null;

    private int $statBatchCounter = 0;

    private array $contact = [];

    public function __construct(
        private MailHelper $mailer,
        private StatHelper $statHelper,
        private DoNotContact $dncModel,
        private TranslatorInterface $translator,
    ) {
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
    public function finalFlush(): void
    {
        $this->flush();
        $this->statHelper->deletePending();
        $this->statHelper->reset();

        $this->processBadEmails();
    }

    /**
     * Use an Email entity to populate content, from, etc.
     *
     * @param array $channel ['channelName', 'channelId']
     *
     * @return $this
     */
    public function setEmail(Email $email, array $channel = [], array $customHeaders = [], array $assetAttachments = [], string $emailType = null)
    {
        // Flush anything that's pending from a previous email
        $this->flush();

        // Enable the queue if applicable to the transport
        $this->mailer->enableQueue();

        if ($this->mailer->setEmail($email, true, $assetAttachments)) {
            $this->mailer->setEmailType($emailType);
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
     * @param int|null $id
     *
     * @return $this
     */
    public function setListId($id)
    {
        $this->listId = empty($id) ? null : (int) $id;

        return $this;
    }

    /**
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
        $this->mailer->setIdHash(); // auto generates

        try {
            if (!$this->mailer->addTo($contact['email'], $contact['firstname'].' '.$contact['lastname'])) {
                $this->failContact();
            }
        } catch (BatchQueueMaxException) {
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
    public function send(): void
    {
        if ($this->mailer->inTokenizationMode()) {
            [$success, $errors] = $this->queueTokenizedEmail();
        } else {
            [$success, $errors] = $this->sendStandardEmail();
        }

        // queue or send the message
        if (!$success) {
            unset($errors['failures']);
            $this->failContact(false, implode('; ', (array) $errors));
        }
    }

    /**
     * Reset everything.
     */
    public function reset(): void
    {
        $this->badEmails         = [];
        $this->errorMessages     = [];
        $this->failedContacts    = [];
        $this->emailEntityErrors = null;
        $this->emailEntityId     = null;
        $this->emailSentCounts   = [];
        $this->listId            = null;
        $this->statBatchCounter  = 0;
        $this->contact           = [];

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
     * @param bool  $hasBadEmail
     * @param array $errorMessages
     *
     * @throws FailedToSendToContactException
     */
    protected function failContact($hasBadEmail = true, $errorMessages = null)
    {
        if (null === $errorMessages) {
            // Clear the errors so it doesn't stop the next send
            $errorMessages = implode('; ', (array) $this->mailer->getErrors());
        } elseif (is_array($errorMessages)) {
            $errorMessages = implode('; ', $errorMessages);
        }

        $this->errorMessages[$this->contact['id']]  = $errorMessages;
        $this->failedContacts[$this->contact['id']] = $this->contact['email'];

        try {
            $stat = $this->statHelper->getStat($this->contact['email']);
            $this->downEmailSentCount($stat->getEmailId());
            $this->statHelper->markForDeletion($stat);
        } catch (StatNotFoundException) {
        }

        if ($hasBadEmail) {
            $this->badEmails[$this->contact['id']] = $this->contact['email'];
        }

        throw new FailedToSendToContactException($errorMessages);
    }

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
            } catch (StatNotFoundException) {
                continue;
            }

            // Add lead ID to list of failures
            $this->failedContacts[$stat->getLeadId()]  = $failedEmail;
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

    protected function queueTokenizedEmail(): array
    {
        [$queued, $queueErrors] = $this->mailer->queue(true, MailHelper::QUEUE_RETURN_ERRORS);

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

        // Create the stat to ensure it is available for emails sent
        $this->createContactStatEntry($this->contact['email']);

        // Now send but don't redispatch the event
        return $this->mailer->queue(false, MailHelper::QUEUE_RETURN_ERRORS);
    }
}
