<?php

namespace Mautic\SmsBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\DecisionEvent;
use Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException;
use Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException;
use Mautic\CampaignBundle\Executioner\Exception\CannotProcessEventException;
use Mautic\CampaignBundle\Executioner\RealTimeExecutioner;
use Mautic\CampaignBundle\Executioner\Scheduler\Exception\NotSchedulableException;
use Mautic\SmsBundle\Event\ReplyEvent;
use Mautic\SmsBundle\Form\Type\CampaignReplyType;
use Mautic\SmsBundle\Helper\ReplyHelper;
use Mautic\SmsBundle\Sms\TransportChain;
use Mautic\SmsBundle\SmsEvents;

final readonly class CampaignReplySubscriber
{
    public const string TYPE = 'sms.reply';

    public function __construct(
        private TransportChain $transportChain,
        private RealTimeExecutioner $realTimeExecutioner,
    ) {
    }

    #[AsEventListener(event: CampaignBuilderEvent::class)]
    public function onCampaignBuild(CampaignBuilderEvent $event): void
    {
        if (0 === count($this->transportChain->getEnabledTransports())) {
            return;
        }

        $event->addDecision(
            self::TYPE,
            [
                'label'       => 'mautic.campaign.sms.reply',
                'description' => 'mautic.campaign.sms.reply.tooltip',
                'eventName'   => SmsEvents::ON_CAMPAIGN_REPLY,
                'formType'    => CampaignReplyType::class,
            ]
        );
    }

    #[AsEventListener(event: SmsEvents::ON_CAMPAIGN_REPLY)]
    public function onCampaignReply(DecisionEvent $decisionEvent): void
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
     * @throws LogNotProcessedException
     * @throws LogPassedAndFailedException
     * @throws CannotProcessEventException
     * @throws NotSchedulableException
     */
    #[AsEventListener(event: ReplyEvent::class)]
    public function onReply(ReplyEvent $event): void
    {
        $this->realTimeExecutioner->execute(self::TYPE, $event, 'sms');
    }
}
