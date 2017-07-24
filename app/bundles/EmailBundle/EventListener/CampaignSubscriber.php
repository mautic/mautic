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

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\ChannelBundle\Model\MessageQueueModel;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Form\DataTransformer\ArrayStringTransformer;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailOpenEvent;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;

/**
 * Class CampaignSubscriber.
 */
class CampaignSubscriber extends CommonSubscriber
{
    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var EmailModel
     */
    protected $emailModel;

    /**
     * @var EmailModel
     */
    protected $messageQueueModel;

    /**
     * @var EventModel
     */
    protected $campaignEventModel;

    /**
     * CampaignSubscriber constructor.
     *
     * @param LeadModel         $leadModel
     * @param EmailModel        $emailModel
     * @param EventModel        $eventModel
     * @param MessageQueueModel $messageQueueModel
     */
    public function __construct(
        LeadModel $leadModel,
        EmailModel $emailModel,
        EventModel $eventModel,
        MessageQueueModel $messageQueueModel
    ) {
        $this->leadModel          = $leadModel;
        $this->emailModel         = $emailModel;
        $this->campaignEventModel = $eventModel;
        $this->messageQueueModel  = $messageQueueModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD       => ['onCampaignBuild', 0],
            EmailEvents::EMAIL_ON_OPEN              => ['onEmailOpen', 0],
            EmailEvents::ON_CAMPAIGN_TRIGGER_ACTION => [
                ['onCampaignTriggerActionSendEmailToContact', 0],
                ['onCampaignTriggerActionSendEmailToUser', 1],
            ],
            EmailEvents::ON_CAMPAIGN_TRIGGER_DECISION => ['onCampaignTriggerDecision', 0],
        ];
    }

    /**
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
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

        $event->addAction(
            'email.send',
            [
                'label'           => 'mautic.email.campaign.event.send',
                'description'     => 'mautic.email.campaign.event.send_descr',
                'eventName'       => EmailEvents::ON_CAMPAIGN_TRIGGER_ACTION,
                'formType'        => 'emailsend_list',
                'formTypeOptions' => ['update_select' => 'campaignevent_properties_email', 'with_email_types' => true],
                'formTheme'       => 'MauticEmailBundle:FormTheme\EmailSendList',
                'channel'         => 'email',
                'channelIdField'  => 'email',
            ]
        );

        $event->addAction(
            'email.send.to.user',
            [
                'label'          => 'mautic.email.campaign.event.send.to.user',
                'description'    => 'mautic.email.campaign.event.send.to.user_descr',
                'eventName'      => EmailEvents::ON_CAMPAIGN_TRIGGER_ACTION,
                'formType'       => 'email_to_user',
                'formTheme'      => 'MauticEmailBundle:FormTheme\EmailSendList',
                'channel'        => 'email',
                'channelIdField' => 'email',
            ]
        );
    }

    /**
     * Trigger campaign event for opening of an email.
     *
     * @param EmailOpenEvent $event
     */
    public function onEmailOpen(EmailOpenEvent $event)
    {
        $email = $event->getEmail();

        if ($email !== null) {
            $this->campaignEventModel->triggerEvent('email.open', $email, 'email', $email->getId());
        }
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerDecision(CampaignExecutionEvent $event)
    {
        $eventDetails = $event->getEventDetails();
        $eventParent  = $event->getEvent()['parent'];

        if ($eventDetails == null) {
            return $event->setResult(false);
        }

        //check to see if the parent event is a "send email" event and that it matches the current email opened
        if (!empty($eventParent) && $eventParent['type'] === 'email.send') {
            return $event->setResult($eventDetails->getId() === (int) $eventParent['properties']['email']);
        }

        return $event->setResult(false);
    }

    /**
     * Triggers the action which sends email to contact.
     *
     * @param CampaignExecutionEvent $event
     *
     * @return CampaignExecutionEvent|null
     */
    public function onCampaignTriggerActionSendEmailToContact(CampaignExecutionEvent $event)
    {
        if (!$event->checkContext('email.send')) {
            return;
        }

        $leadCredentials = $event->getLeadFields();

        if (empty($leadCredentials['email'])) {
            return $event->setFailed('Contact does not have an email');
        }

        $config  = $event->getConfig();
        $emailId = (int) $config['email'];
        $email   = $this->emailModel->getEntity($emailId);

        if (!$email || !$email->isPublished()) {
            return $event->setFailed('Email not found or published');
        }

        $emailSent = false;
        $type      = (isset($config['email_type'])) ? $config['email_type'] : 'transactional';
        $options   = [
            'source'         => ['campaign.event', $event->getEvent()['id']],
            'email_attempts' => (isset($config['attempts'])) ? $config['attempts'] : 3,
            'email_priority' => (isset($config['priority'])) ? $config['priority'] : 2,
            'email_type'     => $type,
            'return_errors'  => true,
            'dnc_as_error'   => true,
        ];

        $event->setChannel('email', $emailId);

        // Determine if this email is transactional/marketing
        $stats = [];
        if ('marketing' == $type) {
            // Determine if this lead has received the email before
            $leadIds   = implode(',', [$leadCredentials['id']]);
            $stats     = $this->emailModel->getStatRepository()->checkContactsSentEmail($leadIds, $emailId);
            $emailSent = true; // Assume it was sent to prevent the campaign event from getting rescheduled over and over
        }

        if (empty($stats)) {
            $emailSent = $this->emailModel->sendEmail($email, $leadCredentials, $options);
        }

        if (is_array($emailSent)) {
            $errors = implode('<br />', $emailSent);

            // Add to the metadata of the failed event
            $emailSent = [
                'result' => false,
                'errors' => $errors,
            ];
        } elseif (true !== $emailSent) {
            $emailSent = [
                'result' => false,
                'errors' => $emailSent,
            ];
        }

        return $event->setResult($emailSent);
    }

    /**
     * Triggers the action which sends email to user, contact owner or specified email addresses.
     *
     * @param CampaignExecutionEvent $event
     *
     * @return CampaignExecutionEvent|null
     */
    public function onCampaignTriggerActionSendEmailToUser(CampaignExecutionEvent $event)
    {
        if (!$event->checkContext('email.send.to.user')) {
            return;
        }

        $config  = $event->getConfig();
        $emailId = (int) $config['useremail']['email'];
        $email   = $this->emailModel->getEntity($emailId);

        if (!$email || !$email->isPublished()) {
            $event->setFailed('Email not found or published');

            return;
        }

        $transformer     = new ArrayStringTransformer();
        $leadCredentials = $event->getLeadFields();
        $toOwner         = empty($config['to_owner']) ? false : $config['to_owner'];
        $ownerId         = empty($leadCredentials['owner_id']) ? null : $leadCredentials['owner_id'];
        $userIds         = empty($config['user_id']) ? [] : $config['user_id'];
        $to              = empty($config['to']) ? [] : $transformer->reverseTransform($config['to']);
        $cc              = empty($config['cc']) ? [] : $transformer->reverseTransform($config['cc']);
        $bcc             = empty($config['bcc']) ? [] : $transformer->reverseTransform($config['bcc']);
        $users           = $this->transformToUserIds($userIds, $ownerId);
        $tokens          = []; // Todo: find the real tokens

        $errors = $this->emailModel->sendEmailToUser($email, $users, $leadCredentials, $tokens, [], false, $to, $cc, $bcc);

        if ($errors) {
            $event->setFailed(implode(', ', $errors));
        }

        return $event->setResult(empty($errors));
    }

    /**
     * Transform user IDs and owner ID in format we get them from the campaign
     * event form to the format the sendEmailToUser expects it.
     * The owner ID will be added only if it's not already present in the user IDs array.
     *
     * @param array $userIds
     * @param int   $ownerId
     *
     * @return array
     */
    public function transformToUserIds(array $userIds, $ownerId)
    {
        $users = [];

        if ($userIds) {
            foreach ($userIds as $userId) {
                $users[] = ['id' => $userId];
            }
        }

        if ($ownerId && !in_array($ownerId, $userIds)) {
            $users[] = ['id' => $ownerId];
        }

        return $users;
    }
}
