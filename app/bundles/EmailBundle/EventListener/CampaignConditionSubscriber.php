<?php

namespace Mautic\EmailBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Exception\InvalidEmailException;
use Mautic\EmailBundle\Helper\EmailValidator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignConditionSubscriber implements EventSubscriberInterface
{
    /**
     * @var EmailValidator
     */
    private $validator;

    public function __construct(EmailValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD          => ['onCampaignBuild', 0],
            EmailEvents::ON_CAMPAIGN_TRIGGER_CONDITION => ['onCampaignTriggerCondition', 0],
        ];
    }

    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        $event->addCondition(
            'email.validate.address',
            [
                'label'       => 'mautic.email.campaign.event.validate_address',
                'description' => 'mautic.email.campaign.event.validate_address_descr',
                'eventName'   => EmailEvents::ON_CAMPAIGN_TRIGGER_CONDITION,
            ]
        );
    }

    public function onCampaignTriggerCondition(CampaignExecutionEvent $event)
    {
        try {
            $this->validator->validate($event->getLead()->getEmail(), true);
        } catch (InvalidEmailException $exception) {
            return $event->setResult(false);
        }

        return $event->setResult(true);
    }
}
