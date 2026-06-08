<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\EventListener;

use Mautic\CampaignBundle\Tests\Functional\Fixtures\FixtureHelper;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\ReportBundle\Tests\Functional\AbstractReportSubscriberTestCase;
use PHPUnit\Framework\Assert;

class ReportSubscriberFunctionalTest extends AbstractReportSubscriberTestCase
{
    public function testCampaignLeadLogReportWithDncListColumn(): void
    {
        $leads[] = $this->createContact('test1@example.com');
        $leads[] = $this->createContact('test2@example.com');
        $leads[] = $this->createContact('test3@example.com');
        $this->em->flush();

        $this->createDnc('email', $leads[0], DoNotContact::BOUNCED);
        $this->createDnc('email', $leads[1], DoNotContact::MANUAL);
        $this->createDnc('email', $leads[2], DoNotContact::UNSUBSCRIBED);
        $this->createDnc('sms', $leads[2], DoNotContact::MANUAL);
        $this->em->flush();

        // execute campaign
        $fixtureHelper = new FixtureHelper($this->em);
        $campaign      = $fixtureHelper->createCampaign('Scheduled event test');
        $fixtureHelper->addContactToCampaign($leads[0], $campaign);
        $fixtureHelper->addContactToCampaign($leads[1], $campaign);
        $fixtureHelper->addContactToCampaign($leads[2], $campaign);
        $fixtureHelper->createCampaignWithScheduledEvent($campaign);
        $this->em->flush();
        $commandResult = $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign->getId()]);
        Assert::assertStringContainsString('3 total events were scheduled', $commandResult->getDisplay());

        $report = $this->createReport(
            source: 'campaign_lead_event_log',
            columns: ['l.id', 'e.type', 'dnc_preferences'],
            filters: [
                [
                    'column'    => 'dnc_preferences',
                    'glue'      => 'and',
                    'dynamic'   => null,
                    'condition' => 'in',
                    'value'     => [
                        'email:'.DoNotContact::UNSUBSCRIBED,
                        'email:'.DoNotContact::BOUNCED,
                    ],
                ],
            ],
            order: [['column' => 'l.id', 'direction' => 'ASC']]
        );

        $expectedReport = [
            // id, event type, dnc_preferences
            [(string) $leads[0]->getId(), 'lead.changepoints', 'DNC Bounced: Email'],
            [(string) $leads[2]->getId(), 'lead.changepoints', 'DNC Manually Unsubscribed: Text Message, DNC Unsubscribed: Email'],
        ];
        $this->verifyReport($report->getId(), $expectedReport);
        $this->verifyApiReport($report->getId(), $expectedReport);
    }

    private function createContact(string $email): Lead
    {
        $contact = new Lead();
        $contact->setEmail($email);
        $this->em->persist($contact);

        return $contact;
    }

    public function createDnc(string $channel, Lead $contact, int $reason): DoNotContact
    {
        $dnc = new DoNotContact();
        $dnc->setChannel($channel);
        $dnc->setLead($contact);
        $dnc->setReason($reason);
        $dnc->setDateAdded(new \DateTime());
        $this->em->persist($dnc);

        return $dnc;
    }
}
