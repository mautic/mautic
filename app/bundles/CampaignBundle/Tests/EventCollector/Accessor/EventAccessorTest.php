<?php

namespace Mautic\CampaignBundle\Tests\EventCollector\Accessor;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ConditionAccessor;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\DecisionAccessor;
use Mautic\CampaignBundle\EventCollector\Accessor\EventAccessor;
use Mautic\EmailBundle\Form\Type\EmailClickDecisionType;
use Mautic\LeadBundle\Form\Type\CampaignEventLeadCampaignsType;
use Mautic\LeadBundle\Form\Type\CompanyChangeScoreActionType;

class EventAccessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array
     */
    private $events = [
        Event::TYPE_ACTION    => [
            'lead.scorecontactscompanies' => [
                'label'          => 'Add to company\'s score',
                'description'    => 'This action will add the specified value to the company\'s existing score',
                'formType'       => CompanyChangeScoreActionType::class,
                'batchEventName' => 'mautic.lead.on_campaign_trigger_action',
            ],
        ],
        Event::TYPE_CONDITION => [
            'lead.campaigns' => [
                'label'       => 'Contact campaigns',
                'description' => 'Condition based on a contact campaigns.',
                'formType'    => CampaignEventLeadCampaignsType::class,
                'formTheme'   => 'MauticLeadBundle:FormTheme\\ContactCampaignsCondition',
                'eventName'   => 'mautic.lead.on_campaign_trigger_condition',
            ],
        ],
        Event::TYPE_DECISION  => [
            'email.click' => [
                'label'                  => 'Clicks email',
                'description'            => 'Trigger actions when an email is clicked. Connect a Send Email action to the top of this decision.',
                'eventName'              => 'mautic.email.on_campaign_trigger_decision',
                'formType'               => EmailClickDecisionType::class,
                'connectionRestrictions' => [
                    'source' => [
                        'action' => [
                            'email.send',
                        ],
                    ],
                ],
            ],
        ],
    ];

    public function testEventsArrayIsBuiltWithAccessors(): void
    {
        $eventAccessor = new EventAccessor($this->events);

        // Actions
        $this->assertCount(1, $eventAccessor->getActions());
        $accessor = $eventAccessor->getAction('lead.scorecontactscompanies');
        $this->assertInstanceOf(ActionAccessor::class, $accessor);
        $this->assertEquals(
            $this->events[Event::TYPE_ACTION]['lead.scorecontactscompanies']['batchEventName'],
            $accessor->getBatchEventName()
        );

        // Conditions
        $this->assertCount(1, $eventAccessor->getConditions());
        $accessor = $eventAccessor->getCondition('lead.campaigns');
        $this->assertInstanceOf(ConditionAccessor::class, $accessor);
        $this->assertEquals(
            $this->events[Event::TYPE_CONDITION]['lead.campaigns']['eventName'],
            $accessor->getEventName()
        );

        // Decisions
        $this->assertCount(1, $eventAccessor->getDecisions());
        $accessor = $eventAccessor->getDecision('email.click');
        $this->assertInstanceOf(DecisionAccessor::class, $accessor);
        $this->assertEquals(
            $this->events[Event::TYPE_DECISION]['email.click']['eventName'],
            $accessor->getEventName()
        );
    }
}
