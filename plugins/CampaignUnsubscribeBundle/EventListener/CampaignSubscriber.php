<?php

/*
 * @copyright   2017 Partout D.N.A. All rights reserved
 * @author      Partout D.N.A.
 *
 * @link        https://partout.nl
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CampaignUnsubscribeBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use MauticPlugin\CampaignUnsubscribeBundle\CampaignUnsubscribeEvents;

/**
 * Class CampaignSubscriber
 * @package MauticPlugin\CampaignUnsubscribeBundle\EventListener
 */
class CampaignSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD => [
                'onCampaignBuild', 0
            ],
            CampaignUnsubscribeEvents::VALIDATE_UNSUBSCRIBE => [
                'onValidateUnsubscribe', 0
            ]
        ];
    }

    /**
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        // Register custom decision (executes when a lead "makes a decision" i.e. executes some direct action
        $event->addDecision(
            'campaignunsubscribe.unsubscribe',
            array(
                'label' => 'plugin.campaignunsubscribe.campaign.unsubscribe.label',
                'description' => 'plugin.campaignunsubscribe.campaign.unsubscribe.description',
                'eventName' => CampaignUnsubscribeEvents::VALIDATE_UNSUBSCRIBE,
                'formType' => false,
                'formTypeOptions' => array()
            )
        );
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onValidateUnsubscribe(CampaignExecutionEvent $event)
    {
        if (($event->getEventDetails()['doNotContact'] && $this->params['campaign_unsubscribe_remove_campaign_donotcontact']) || in_array($event->getEvent()['campaign']['id'], $event->getEventDetails()['toBeUnsubscribed'])) {
            $event->setResult(true);
        } else {
            $event->setResult(false);
        }
    }
}