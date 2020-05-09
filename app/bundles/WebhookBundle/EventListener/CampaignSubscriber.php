<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event as Events;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\WebhookBundle\Event\SendWebhookEvent;
use Mautic\WebhookBundle\Form\Type\CampaignEventSendWebhookType;
use Mautic\WebhookBundle\Helper\CampaignHelper;
use Mautic\WebhookBundle\WebhookEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignSubscriber implements EventSubscriberInterface
{
    /**
     * @var CampaignHelper
     */
    private $campaignHelper;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(CampaignHelper $campaignHelper, EventDispatcherInterface $dispatcher)
    {
        $this->campaignHelper = $campaignHelper;
        $this->dispatcher     = $dispatcher;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD         => ['onCampaignBuild', 0],
            WebhookEvents::ON_CAMPAIGN_TRIGGER_ACTION => ['onCampaignTriggerAction', 0],
        ];
    }

    /**
     * @return CampaignExecutionEvent
     */
    public function onCampaignTriggerAction(CampaignExecutionEvent $event)
    {
        if ($event->checkContext('campaign.sendwebhook')) {
            try {
                $this->campaignHelper->fireWebhook($event->getConfig(), $event->getLead());
                $event->setResult(true);

                if ($this->dispatcher->hasListeners(WebhookEvents::ON_WEBHOOK_RESPONSE)) {
                    $sendWebhookEvent = new SendWebhookEvent($this->campaignHelper->getResponse(), $event->getLead());
                    $this->dispatcher->dispatch(WebhookEvents::ON_WEBHOOK_RESPONSE, $sendWebhookEvent);
                    unset($sendWebhookEvent);
                }
            } catch (\Exception $e) {
                $event->setFailed($e->getMessage());
            }
        }
    }

    /**
     * Add event triggers and actions.
     */
    public function onCampaignBuild(Events\CampaignBuilderEvent $event)
    {
        $sendWebhookAction = [
            'label'       => 'mautic.webhook.event.sendwebhook',
            'description' => 'mautic.webhook.event.sendwebhook_desc',
            'formType'    => CampaignEventSendWebhookType::class,
            'eventName'   => WebhookEvents::ON_CAMPAIGN_TRIGGER_ACTION,
        ];
        $event->addAction('campaign.sendwebhook', $sendWebhookAction);
    }
}
