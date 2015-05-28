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
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailEvent;
use Mautic\EmailBundle\Event\EmailOpenEvent;

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
            EmailEvents::EMAIL_ON_OPEN        => array('onEmailOpen', 0)
        );
    }

    /**
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        $trigger = array(
            'label'           => 'mautic.email.campaign.event.open',
            'description'     => 'mautic.email.campaign.event.open_descr',
            'callback'        => array('\\Mautic\\EmailBundle\\Helper\\CampaignEventHelper', 'validateEmailTrigger')
        );
        $event->addLeadDecision('email.open', $trigger);

        $action = array(
            'label'           => 'mautic.email.campaign.event.send',
            'description'     => 'mautic.email.campaign.event.send_descr',
            'callback'        => array('\\Mautic\\EmailBundle\\Helper\\CampaignEventHelper', 'sendEmailAction'),
            'formType'        => 'emailsend_list',
            'formTypeOptions' => array('update_select' => 'campaignevent_properties_email'),
            'formTheme'       => 'MauticEmailBundle:FormTheme\EmailSendList'
        );
        $event->addAction('email.send', $action);
    }

    /**
     * Trigger campaign event for opening of an email
     *
     * @param EmailEvent $event
     */
    public function onEmailOpen(EmailOpenEvent $event)
    {
        $email = $event->getEmail();
        $this->factory->getModel('campaign')->triggerEvent('email.open', $email, 'email.open' . $email->getId());
    }
}