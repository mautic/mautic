<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\DecisionEvent;
use Mautic\CampaignBundle\Executioner\RealTimeExecutioner;
use Mautic\SmsBundle\Event\ReplyEvent;
use Mautic\SmsBundle\Helper\ReplyHelper;
use Mautic\SmsBundle\Sms\TransportChain;
use Mautic\SmsBundle\SmsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class CampaignReplySubscriber.
 */
class CampaignDeliverySubscriber implements EventSubscriberInterface
{
    const TYPE_DELIVERY = 'sms.delivery';
    const TYPE_READ     = 'sms.read';

    /**
     * @var TransportChain
     */
    private $transportChain;

    /**
     * @var RealTimeExecutioner
     */
    private $realTimeExecutioner;

    /**
     * CampaignReplySubscriber constructor.
     *
     * @param TransportChain      $transportChain
     * @param RealTimeExecutioner $realTimeExecutioner
     */
    public function __construct(TransportChain $transportChain, RealTimeExecutioner $realTimeExecutioner)
    {
        $this->transportChain      = $transportChain;
        $this->realTimeExecutioner = $realTimeExecutioner;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD => ['onCampaignBuild', 0],
            SmsEvents::ON_CAMPAIGN_DELIVERY   => ['onCampaignDelivery', 0],
            SmsEvents::ON_DELIVERY            => ['onDelivery', 0],
        ];
    }

    /**
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        if (count($this->transportChain->getEnabledTransports()) === 0) {
            return;
        }

        $event->addDecision(
            self::TYPE_DELIVERY,
            [
                'label'       => 'mautic.campaign.sms.delivery',
                'description' => 'mautic.campaign.sms.delivery.tooltip',
                'eventName'   => SmsEvents::ON_CAMPAIGN_DELIVERY,
            ]
        );

        $event->addDecision(
            self::TYPE_READ,
            [
                'label'       => 'mautic.campaign.sms.read',
                'description' => 'mautic.campaign.sms.read.tooltip',
                'eventName'   => SmsEvents::ON_CAMPAIGN_DELIVERY,
            ]
        );
    }

    /**
     * @param DecisionEvent $decisionEvent
     */
    public function onCampaignDelivery(DecisionEvent $decisionEvent)
    {
        /** @var ReplyEvent $replyEvent */
        $replyEvent = $decisionEvent->getPassthrough();
        $pattern    = $decisionEvent->getLog()->getEvent()->getProperties()['pattern'];
        if (empty($pattern)) {
            // Assume any reply
            $decisionEvent->setAsApplicable();

            return;
        }

        if (!ReplyHelper::matches($pattern, $replyEvent->getMessage())) {
            // It does not match so ignore

            return;
        }

        $decisionEvent->setChannel('sms');
        $decisionEvent->setAsApplicable();
    }

    /**
     * @param ReplyEvent $event
     *
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException
     * @throws \Mautic\CampaignBundle\Executioner\Exception\CannotProcessEventException
     * @throws \Mautic\CampaignBundle\Executioner\Scheduler\Exception\NotSchedulableException
     */
    public function onReply(ReplyEvent $event)
    {
        $this->realTimeExecutioner->execute(self::TYPE, $event, 'sms');
    }
}
