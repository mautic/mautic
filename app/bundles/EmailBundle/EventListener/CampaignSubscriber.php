<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\EmailBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailOpenEvent;
use Mautic\LeadBundle\Model\LeadModel;

/**
 * Class CampaignSubscriber
 *
 * @package Mautic\EmailBundle\EventListener
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
     * @var EventModel
     */
    protected $campaignEventModel;

    /**
     * CampaignSubscriber constructor.
     *
     * @param LeadModel  $leadModel
     * @param EmailModel $emailModel
     * @param EventModel $eventModel
     */
    public function __construct(LeadModel $leadModel, EmailModel $emailModel, EventModel $eventModel)
    {
        $this->leadModel  = $leadModel;
        $this->emailModel = $emailModel;
        $this->campaignEventModel = $eventModel;
    }

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD         => ['onCampaignBuild', 0],
            EmailEvents::EMAIL_ON_OPEN                => ['onEmailOpen', 0],
            EmailEvents::ON_CAMPAIGN_TRIGGER_ACTION   => ['onCampaignTriggerAction', 0],
            EmailEvents::ON_CAMPAIGN_TRIGGER_DECISION => ['onCampaignTriggerDecision', 0]
        ];
    }

    /**
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        $trigger = [
            'label'             => 'mautic.email.campaign.event.open',
            'description'       => 'mautic.email.campaign.event.open_descr',
            'eventName'         => EmailEvents::ON_CAMPAIGN_TRIGGER_DECISION,
            'associatedActions' => ['email.send'],
        ];
        $event->addLeadDecision('email.open', $trigger);

        $action = [
            'label'           => 'mautic.email.campaign.event.send',
            'description'     => 'mautic.email.campaign.event.send_descr',
            'eventName'       => EmailEvents::ON_CAMPAIGN_TRIGGER_ACTION,
            'formType'        => 'emailsend_list',
            'formTypeOptions' => ['update_select' => 'campaignevent_properties_email', 'with_email_types' => true],
            'formTheme'       => 'MauticEmailBundle:FormTheme\EmailSendList'
        ];
        $event->addAction('email.send', $action);
    }

    /**
     * Trigger campaign event for opening of an email
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
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerAction(CampaignExecutionEvent $event)
    {
        $emailSent       = false;
        $lead            = $event->getLead();
        $leadCredentials = ($lead instanceof Lead) ? $lead->getProfileFields() : $lead;
        $leadCredentials['owner_id'] = (
            ($lead instanceof Lead) && ($owner = $lead->getOwner())
        ) ? $owner->getId() : 0;

        if (!empty($leadCredentials['email'])) {
            $config  = $event->getConfig();
            $emailId = (int) $config['email'];

            $email = $this->emailModel->getEntity($emailId);

            if ($email != null && $email->isPublished()) {
                // Determine if this email is transactional/marketing
                $type = (isset($config['email_type'])) ? $config['email_type'] : 'transactional';
                if ('marketing' == $type) {
                    // Determine if this lead has received the email before
                    $stats = $this->emailModel->getStatRepository()->findContactEmailStats($leadCredentials['id'], $emailId);

                    if (count($stats)) {
                        // Already sent
                        return $event->setResult(true);
                    }
                }

                $eventDetails = $event->getEventDetails();
                $options      = ['source' => ['campaign', $eventDetails['campaign']['id']]];
                $emailSent    = $this->emailModel->sendEmail($email, $leadCredentials, $options);
            }

            $event->setChannel('email', $emailId);
        }

        return $event->setResult($emailSent);
    }
}
