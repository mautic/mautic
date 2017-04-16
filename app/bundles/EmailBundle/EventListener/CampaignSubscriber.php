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
    public function __construct(LeadModel $leadModel, EmailModel $emailModel, EventModel $eventModel, MessageQueueModel $messageQueueModel)
    {
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
            CampaignEvents::CAMPAIGN_ON_BUILD         => ['onCampaignBuild', 0],
            EmailEvents::EMAIL_ON_OPEN                => ['onEmailOpen', 0],
            EmailEvents::ON_CAMPAIGN_TRIGGER_ACTION   => ['onCampaignTriggerAction', 0],
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

        $event->addDecision(
            'email.click',
            [
                'label'                  => 'mautic.email.campaign.event.click',
                'description'            => 'mautic.email.campaign.event.click_descr',
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
        $eventConfig = $event->getConfig();

        if ($eventDetails == null) {
            return $event->setResult(false);
        }

        //check to see if the parent event is a "send email" event and that it matches the current email opened or clicked
        if (!empty($eventParent) && $eventParent['type'] === 'email.send') {

            // click decision with url match
            if($event->checkContext('email.click')) {
                $hit = $eventDetails;
                $limitToUrl = str_replace(
                    ['\|', '\$', '\^'],
                    ['|', '$', '^'],
                    preg_quote(trim($eventConfig['url']), '/')
                );
                $currentUrl = $hit->getUrl();
                preg_match('/'.$limitToUrl.'/', $currentUrl, $matches);
                if ((!$limitToUrl || !empty($matches[0])) && $eventDetails->getEmail()->getId() == (int)$eventParent['properties']['email']) {
                    return $event->setResult(true);
                }
            } elseif ($event->checkContext('email.open')) {
                return $event->setResult($eventDetails->getId() === (int)$eventParent['properties']['email']);
            }
        }

        return $event->setResult(false);
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerAction(CampaignExecutionEvent $event)
    {
        $emailSent                   = false;
        $lead                        = $event->getLead();
        $leadCredentials             = ($lead instanceof Lead) ? $lead->getProfileFields() : $lead;
        $leadCredentials['owner_id'] = (
            ($lead instanceof Lead) && ($owner = $lead->getOwner())
        ) ? $owner->getId() : 0;

        if (!empty($leadCredentials['email'])) {
            $config  = $event->getConfig();
            $emailId = (int) $config['email'];

            $email   = $this->emailModel->getEntity($emailId);
            $type    = (isset($config['email_type'])) ? $config['email_type'] : 'transactional';
            $options = [
                'source'         => ['campaign.event', $event->getEvent()['id']],
                'email_attempts' => (isset($config['attempts'])) ? $config['attempts'] : 3,
                'email_priority' => (isset($config['priority'])) ? $config['priority'] : 2,
                'email_type'     => $type,
                'return_errors'  => true,
            ];

            $event->setChannel('email', $emailId);

            if ($email != null && $email->isPublished()) {
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
                }
            } else {
                return $event->setFailed('Email not found or published');
            }
        } else {
            return $event->setFailed('Contact does not have an email');
        }

        return $event->setResult($emailSent);
    }
}
