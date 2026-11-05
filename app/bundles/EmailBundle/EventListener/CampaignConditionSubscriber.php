<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\EventListener;

use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\ConditionEvent;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Exception\InvalidEmailException;
use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final readonly class CampaignConditionSubscriber
{
    public function __construct(
        private EmailValidator $validator,
    ) {
    }

    #[AsEventListener(
        event: CampaignBuilderEvent::class,
    )]
    public function onCampaignBuild(CampaignBuilderEvent $event): void
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

    #[AsEventListener(
        event: EmailEvents::ON_CAMPAIGN_TRIGGER_CONDITION,
    )]
    public function onCampaignTriggerCondition(ConditionEvent $event): void
    {
        /** @var Lead $lead */
        $lead = $event->getLead();

        try {
            $this->validator->validate($lead->getEmail(), true);
        } catch (UnexpectedValueException|InvalidEmailException) {
            $event->fail();

            return;
        }

        $event->pass();
    }
}
