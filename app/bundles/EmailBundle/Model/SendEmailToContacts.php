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
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Swiftmailer\Exception\BatchQueueMaxException;
use Mautic\LeadBundle\Entity\DoNotContact as DNC;
use Mautic\LeadBundle\Model\DoNotContact;
use Symfony\Component\Translation\TranslatorInterface;

class SendEmailToContacts
{
    /**
     * @var MailHelper
     */
    protected $mailer;

    /**
     * @var StatRepository
     */
    protected $statRepo;

    /**
     * @var DoNotContact
     */
    protected $dncModel;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var null|string
     */
    protected $singleEmailMode = null;

    /**
     * @var Stat[]
     */
    protected $statEntities = [];

    /**
     * @var Stat[]
     */
    protected $saveEntities = [];

    /**
     * @var Stat[]
     */
    protected $deleteEntities = [];

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var array
     */
    protected $errorMessages = [];

    /**
     * @var array
     */
    protected $badEmails = [];

    /**
     * @var array
     */
    protected $emailSentCounts = [];

    /**
     * @var
     */
    protected $emailEntityErrors;

    /**
     * @var null|int
     */
    protected $emailEntityId;

    /**
     * @var null|int
     */
    protected $listId;

    /**
     * @var int
     */
    protected $statBatchCounter = 0;

    /**
     * Send constructor.
     *
     * @param MailHelper $mailer
     */
    public function __construct(MailHelper $mailer, StatRepository $statRepository, DoNotContact $dncModel, TranslatorInterface $translator)
    {
        $this->mailer     = $mailer;
        $this->statRepo   = $statRepository;
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

    public function finalFlush()
    {
        $this->flush();

        // Persist left over stats
        if (count($this->saveEntities)) {
            $this->statRepo->saveEntities($this->saveEntities);
        }
        if (count($this->deleteEntities)) {
            $this->statRepo->deleteEntities($this->deleteEntities);
        }

        $this->processBadEmails();

        $this->reset();
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

        if ($this->mailer->setEmail($email, true, $slots, $assetAttachments)) {
            $this->mailer->setSource($channel);
            $this->mailer->setCustomHeaders($customHeaders);

            // Note that the entity is set so that addContact does not generate errors
            $this->emailEntityId = $email->getId();
        } else {
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
        $this->listId = (null !== $id) ? (int) $id : $id;

        return $this;
    }

    /**
     * @param array $contact
     * @param array $tokens
     */
    public function sendToContact(array $contact, array $tokens = [])
    {
        if (!$this->emailEntityId) {
            // There was an error configuring the email so auto fail
            $this->failContact($contact, $this->emailEntityErrors);

            return false;
        }

        $this->mailer->setTokens($tokens);
        $this->mailer->setLead($contact);
        $this->mailer->setIdHash(); //auto generates

        try {
            if (!$this->mailer->addTo($contact['email'], $contact['firstname'].' '.$contact['lastname'])) {
                $this->failContact($contact);

                return false;
            }
        } catch (BatchQueueMaxException $e) {
            // Queue full so flush then try again
            $this->flush(false);

            if (!$this->mailer->addTo($contact['email'], $contact['firstname'].' '.$contact['lastname'])) {
                $this->failContact($contact);

                return false;
            }
        }

        return $this->send($contact);
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
        $this->errors            = [];
        $this->emailEntityErrors = null;
        $this->emailEntityId     = null;
        $this->emailSentCounts   = [];
        $this->singleEmailMode   = null;
        $this->listId            = null;
        $this->statBatchCounter  = 0;

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
     * @return array [array $rawErrors, array $errorMessages]
     */
    public function getErrors()
    {
        return [$this->errors, $this->errorMessages];
    }

    /**
     * @param array $contact
     *
     * @return bool
     */
    protected function send(array $contact)
    {
        //queue or send the message
        list($queued, $queueErrors) = $this->mailer->queue(true, MailHelper::QUEUE_RETURN_ERRORS);
        if (!$queued) {
            unset($queueErrors['failures']);
            $this->failContact($contact, implode('; ', $queueErrors));

            return false;
        }

        $this->createContactStatEntry($contact['email']);

        return true;
    }

    /**
     * @param array $contact
     * @param mixed $errorMessages
     */
    protected function failContact(array $contact, $errorMessages = null)
    {
        if (null === $errorMessages) {
            // Clear the errors so it doesn't stop the next send
            $errorMessages = $this->mailer->getErrors();
        }

        $this->errorMessages[$contact['id']] = $errorMessages;
        $this->errors[$contact['id']]        = $contact['email'];
        $this->badEmails[$contact['id']]     = $contact['email'];
    }

    /**
     * @param $sendFailures
     */
    protected function processSendFailures($sendFailures)
    {
        $failedEmailAddresses = $sendFailures['failures'];
        unset($sendFailures['failures']);
        $error = implode('; ', $sendFailures);

        // Prevent the stat from saving
        foreach ($failedEmailAddresses as $failedEmail) {
            /** @var Stat $stat */
            $stat = $this->statEntities[$failedEmail];
            // Add lead ID to list of failures
            $this->errors[$stat->getLead()->getId()]        = $failedEmail;
            $this->errorMessages[$stat->getLead()->getId()] = $error;

            // Down sent counts
            $emailId = $stat->getEmail()->getId();
            $this->downEmailSentCount($emailId);

            if ($stat->getId()) {
                $this->deleteEntities[] = $stat;
            }
            unset($this->statEntities[$failedEmail], $this->saveEntities[$failedEmail]);
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
                    $this->translator->trans('mautic.email.bounce.reason.bad_email'),
                    DNC::BOUNCED,
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

        $stat = $this->saveEntities[$email] = $this->mailer->createEmailStat(false, null, $this->listId);
        $this->upEmailSentCount($stat->getEmail()->getId());

        if (20 === $this->statBatchCounter) {
            // Save in batches of 20 to prevent email loops if the there are issuses with persisting a large number of stats at once
            $this->statRepo->saveEntities($this->saveEntities);
            $this->statBatchCounter = 0;
            $this->saveEntities     = [];
        }
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
}
