<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Doctrine\ORM\ORMException;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException;
use Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException;
use Mautic\CampaignBundle\Executioner\Exception\CannotProcessEventException;
use Mautic\CampaignBundle\Executioner\Exception\NoContactsFoundException;
use Mautic\CampaignBundle\Executioner\RealTimeExecutioner;
use Mautic\CampaignBundle\Executioner\Scheduler\Exception\NotSchedulableException;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailOpenEvent;
use Mautic\EmailBundle\Event\EmailReplyEvent;
use Mautic\EmailBundle\Exception\EmailCouldNotBeSentException;
use Mautic\EmailBundle\Form\Type\EmailClickDecisionType;
use Mautic\EmailBundle\Form\Type\EmailSendType;
use Mautic\EmailBundle\Form\Type\EmailToUserType;
use Mautic\EmailBundle\Helper\UrlMatcher;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\EmailBundle\Model\SendEmailToUser;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class CampaignSubscriber implements EventSubscriberInterface
{
    /**
     * @var EmailModel
     */
    private $emailModel;

    /**
     * @var RealTimeExecutioner
     */
    private $realTimeExecutioner;

    /**
     * @var SendEmailToUser
     */
    private $sendEmailToUser;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        EmailModel $emailModel,
        RealTimeExecutioner $realTimeExecutioner,
        SendEmailToUser $sendEmailToUser,
        TranslatorInterface $translator
    ) {
        $this->emailModel          = $emailModel;
        $this->realTimeExecutioner = $realTimeExecutioner;
        $this->sendEmailToUser     = $sendEmailToUser;
        $this->translator          = $translator;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD       => ['onCampaignBuild', 0],
            EmailEvents::EMAIL_ON_OPEN              => ['onEmailOpen', 0],
            EmailEvents::ON_CAMPAIGN_BATCH_ACTION   => [
                ['onCampaignTriggerActionSendEmailToContact', 0],
                ['onCampaignTriggerActionSendEmailToUser', 1],
            ],
            EmailEvents::ON_CAMPAIGN_TRIGGER_DECISION => ['onCampaignTriggerDecision', 0],
            EmailEvents::EMAIL_ON_REPLY               => ['onEmailReply', 0],
        ];
    }

    public function onCampaignBuild(CampaignBuilderEvent $event): void
    {
        $event->addDecision(
            'email.open',
            [
                'label'                  => 'mautic.email.campaign.event.open',
                'description'            => 'mautic.email.campaign.event.open_descr',
                'eventName'              => EmailEvents::ON_CAMPAIGN_TRIGGER_DECISION,
                'connectionRestrictions' => [
                    'source' => [
                        'action' => [
                            'email.send',
                        ],
                    ],
                ],
            ]
        );

        $event->addDecision(
            'email.click',
            [
                'label'                  => 'mautic.email.campaign.event.click',
                'description'            => 'mautic.email.campaign.event.click_descr',
                'eventName'              => EmailEvents::ON_CAMPAIGN_TRIGGER_DECISION,
                'formType'               => EmailClickDecisionType::class,
                'connectionRestrictions' => [
                    'source' => [
                        'action' => [
                            'email.send',
                        ],
                    ],
                ],
            ]
        );

        $event->addAction(
            'email.send',
            [
                'label'                => 'mautic.email.campaign.event.send',
                'description'          => 'mautic.email.campaign.event.send_descr',
                'batchEventName'       => EmailEvents::ON_CAMPAIGN_BATCH_ACTION,
                'formType'             => EmailSendType::class,
                'formTypeOptions'      => ['update_select' => 'campaignevent_properties_email', 'with_email_types' => true],
                'formTheme'            => 'MauticEmailBundle:FormTheme\EmailSendList',
                'channel'              => 'email',
                'channelIdField'       => 'email',
            ]
        );

        $event->addDecision(
                'email.reply',
                [
                    'label'                  => 'mautic.email.campaign.event.reply',
                    'description'            => 'mautic.email.campaign.event.reply_descr',
                    'eventName'              => EmailEvents::ON_CAMPAIGN_TRIGGER_DECISION,
                    'connectionRestrictions' => [
                        'source' => [
                            'action' => [
                                'email.send',
                            ],
                        ],
                    ],
                ]
            );

        $event->addAction(
            'email.send.to.user',
            [
                'label'                => 'mautic.email.campaign.event.send.to.user',
                'description'          => 'mautic.email.campaign.event.send.to.user_descr',
                'batchEventName'       => EmailEvents::ON_CAMPAIGN_BATCH_ACTION,
                'formType'             => EmailToUserType::class,
                'formTypeOptions'      => ['update_select' => 'campaignevent_properties_useremail_email'],
                'formTheme'            => 'MauticEmailBundle:FormTheme\EmailSendList',
                'channel'              => 'email',
                'channelIdField'       => 'email',
            ]
        );
    }

    /**
     * Trigger campaign event for opening of an email.
     *
     * @throws LogNotProcessedException
     * @throws LogPassedAndFailedException
     * @throws CannotProcessEventException
     * @throws NotSchedulableException
     */
    public function onEmailOpen(EmailOpenEvent $event): void
    {
        $email = $event->getEmail();

        if (null !== $email) {
            $this->realTimeExecutioner->execute('email.open', $email, 'email', $email->getId());
        }
    }

    /**
     * Trigger campaign event for reply to an email.
     *
     * @throws CannotProcessEventException
     * @throws LogNotProcessedException
     * @throws LogPassedAndFailedException
     * @throws NotSchedulableException
     */
    public function onEmailReply(EmailReplyEvent $event): void
    {
        $email = $event->getEmail();
        if (null !== $email) {
            $this->realTimeExecutioner->execute('email.reply', $email, 'email', $email->getId());
        }
    }

    public function onCampaignTriggerDecision(CampaignExecutionEvent $event): CampaignExecutionEvent
    {
        /** @var Email $eventDetails */
        $eventDetails = $event->getEventDetails();
        $eventParent  = $event->getEvent()['parent'];
        $eventConfig  = $event->getConfig();

        if (null == $eventDetails) {
            return $event->setResult(false);
        }

        //check to see if the parent event is a "send email" event and that it matches the current email opened or clicked
        if (!empty($eventParent) && 'email.send' === $eventParent['type']) {
            // click decision
            if ($event->checkContext('email.click')) {
                /** @var Hit $hit */
                $hit = $eventDetails;
                if (in_array((int) $eventParent['properties']['email'], $eventDetails->getEmail()->getRelatedEntityIds())) {
                    if (!empty($eventConfig['urls']['list'])) {
                        $limitToUrls = (array) $eventConfig['urls']['list'];
                        if (UrlMatcher::hasMatch($limitToUrls, $hit->getUrl())) {
                            return $event->setResult(true);
                        }
                    } else {
                        return $event->setResult(true);
                    }
                }

                return $event->setResult(false);
            } elseif ($event->checkContext('email.open')) {
                // open decision
                return $event->setResult(in_array((int) $eventParent['properties']['email'], $eventDetails->getRelatedEntityIds()));
            } elseif ($event->checkContext('email.reply')) {
                // reply decision
                return $event->setResult(in_array((int) $eventParent['properties']['email'], $eventDetails->getRelatedEntityIds()));
            }
        }

        return $event->setResult(false);
    }

    /**
     * Triggers the action which sends email to contacts.
     *
     * @throws ORMException
     * @throws NoContactsFoundException
     */
    public function onCampaignTriggerActionSendEmailToContact(PendingEvent $event): void
    {
        if (!$event->checkContext('email.send')) {
            return;
        }

        $config  = $event->getEvent()->getProperties();
        $emailId = (int) $config['email'];
        $email   = $this->emailModel->getEntity($emailId);

        if (!$email || !$email->isPublished()) {
            $event->passAllWithError($this->translator->trans('mautic.email.campaign.event.failure_missing_email'));

            return;
        }

        $event->setChannel('email', $emailId);

        $type    = (isset($config['email_type'])) ? $config['email_type'] : 'transactional';
        $options = [
            'source'         => ['campaign.event', $event->getEvent()->getId()],
            'email_attempts' => (isset($config['attempts'])) ? $config['attempts'] : 3,
            'email_priority' => (isset($config['priority'])) ? $config['priority'] : 2,
            'email_type'     => $type,
            'return_errors'  => true,
            'dnc_as_error'   => true,
            'customHeaders'  => [
                'X-EMAIL-ID' => $emailId,
            ],
        ];

        // Determine if this email is transactional/marketing
        $pending         = $event->getPending();
        $contacts        = $event->getContacts();
        $contactIds      = $event->getContactIds();
        $credentialArray = [];

        /**
         * @var int
         * @var Lead $contact
         */
        foreach ($contacts as $logId => $contact) {
            $leadCredentials                      = $contact->getProfileFields();
            $leadCredentials['primaryIdentifier'] = $contact->getPrimaryIdentifier();
            // Set owner_id to support the "Owner is mailer" feature
            if ($contact->getOwner()) {
                $leadCredentials['owner_id'] = $contact->getOwner()->getId();
            }

            if (empty($leadCredentials['email'])) {
                // Pass with a note to the UI because no use retrying
                $event->passWithError(
                    $pending->get($logId),
                    $this->translator->trans(
                        'mautic.email.contact_has_no_email',
                        ['%contact%' => $contact->getPrimaryIdentifier()]
                    )
                );
                unset($contactIds[$contact->getId()]);
                continue;
            }

            $credentialArray[$logId] = $leadCredentials;
        }

        if ('marketing' == $type) {
            // Determine if this lead has received the email before and if so, don't send it again
            $stats = $this->emailModel->getStatRepository()->getSentCountForContacts($contactIds, $emailId);

            foreach ($stats as $contactId => $sentCount) {
                /** @var LeadEventLog $log */
                $log = $event->findLogByContactId($contactId);
                // Pass with a note to the UI because no use retrying
                $event->passWithError(
                    $log,
                    $this->translator->trans('mautic.email.contact_already_received_marketing_email', ['%contact%' => $credentialArray[$log->getId()]['primaryIdentifier']])
                );
                unset($credentialArray[$log->getId()]);
            }
        }

        if (count($credentialArray)) {
            $errors = $this->emailModel->sendEmail($email, $credentialArray, $options);

            // Fail those that failed to send
            foreach ($errors as $failedContactId => $reason) {
                $log = $event->findLogByContactId($failedContactId);
                unset($credentialArray[$log->getId()]);

                if ($this->translator->trans('mautic.email.dnc') === $reason) {
                    // Do not log DNC as errors because they'll be retried rather just let the UI know
                    $event->passWithError($log, $reason);
                    continue;
                }

                $event->fail($log, $reason);
            }

            // Pass everyone else
            foreach (array_keys($credentialArray) as $logId) {
                $event->pass($pending->get($logId));
            }
        }
    }

    /**
     * @throws ORMException
     */
    public function onCampaignTriggerActionSendEmailToUser(PendingEvent $event): void
    {
        if (!$event->checkContext('email.send.to.user')) {
            return;
        }

        $config   = $event->getEvent()->getProperties();
        $contacts = $event->getContacts();
        $pending  = $event->getPending();

        /**
         * @var int
         * @var Lead $contact
         */
        foreach ($contacts as $logId => $contact) {
            try {
                $this->sendEmailToUser->sendEmailToUsers($config, $contact);
                $event->pass($pending->get($logId));
            } catch (EmailCouldNotBeSentException $e) {
                $event->fail($pending->get($logId), $e->getMessage());
            }
        }
    }
}
