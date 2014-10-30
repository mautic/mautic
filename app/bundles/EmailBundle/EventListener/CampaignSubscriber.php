<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\EmailBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailEvent;

/**
 * Class CampaignSubscriber
 *
 * @package Mautic\EmailBundle\EventListener
 */
class CampaignSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            CampaignEvents::CAMPAIGN_ON_BUILD => array('onCampaignBuild', 0),
            EmailEvents::EMAIL_ON_SEND        => array('onEmailSend', 0),
            EmailEvents::EMAIL_ON_OPEN        => array('onEmailOpen', 0)
        );
    }

    /**
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        $trigger = array(
            'group'       => 'mautic.email.campaign.trigger.group',
            'label'       => 'mautic.email.campaign.trigger.open',
            'description' => 'mautic.email.campaign.trigger.open_descr',
            'callback'    => array('\\Mautic\\EmailBundle\\Helper\\CampaignEventHelper', 'validateEmailTrigger'),
            'formType'    => 'campaigntrigger_email'
        );
        $event->addLeadAction('email.open', $trigger);

        $action = array(
            'label'       => 'mautic.email.campaign.action.send',
            'description' => 'mautic.email.campaign.action.send_descr',
            'callback'    => array('\\Mautic\\EmailBundle\\Helper\\CampaignEventHelper', 'sendEmailAction'),
            'formType'    => 'campaignaction_email'
        );
        $event->addOutcome('email.send', $action);
    }

    /**
     * Trigger campaign event for sending of an email
     *
     * @param EmailEvent $event
     */
    public function onEmailSend(EmailEvent $event)
    {
        $email = $event->getEmail();
        $this->factory->getModel('campaign')->triggerEvent('email.send', $email);
    }

    /**
     * Trigger campaign event for opening of an email
     *
     * @param EmailEvent $event
     */
    public function onEmailOpen(EmailEvent $event)
    {
        $email = $event->getEmail();
        $this->factory->getModel('campaign')->triggerEvent('email.open', $email, 'email.open' . $email->getId());
    }
}