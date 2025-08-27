<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\ReportBundle\Tests\Functional\AbstractReportSubscriberTestCase;

class ReportSubscriberFunctionalTest extends AbstractReportSubscriberTestCase
{
    public function testLeadReportWithDncListColumn(): void
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

        $report = $this->createReport(
            source: 'leads',
            columns: ['l.id', 'dnc_preferences'],
            order: [['column' => 'l.id', 'direction' => 'ASC']]
        );

        $expectedReport = [
            // id, dnc_preferences
            [(string) $leads[0]->getId(), 'DNC Bounced: Email'],
            [(string) $leads[1]->getId(), 'DNC Manually Unsubscribed: Email'],
            [(string) $leads[2]->getId(), 'DNC Manually Unsubscribed: Text Message, DNC Unsubscribed: Email'],
        ];
        $this->verifyReport($report->getId(), $expectedReport);
        $this->verifyApiReport($report->getId(), $expectedReport);
    }

    public function testLeadReportWithDncListFilterIn(): void
    {
        $leads[] = $this->createContact('test1@example.com');
        $leads[] = $this->createContact('test2@example.com');
        $leads[] = $this->createContact('test3@example.com');
        $this->em->flush();

        $this->createDnc('email', $leads[0], DoNotContact::BOUNCED);
        $this->createDnc('email', $leads[2], DoNotContact::UNSUBSCRIBED);
        $this->createDnc('sms', $leads[2], DoNotContact::MANUAL);
        $this->em->flush();

        $report = $this->createReport(
            source: 'leads',
            columns: ['l.id', 'dnc_preferences'],
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
            // id, dnc_preferences
            [(string) $leads[0]->getId(), 'DNC Bounced: Email'],
            [(string) $leads[2]->getId(), 'DNC Manually Unsubscribed: Text Message, DNC Unsubscribed: Email'],
        ];
        $this->verifyReport($report->getId(), $expectedReport);
        $this->verifyApiReport($report->getId(), $expectedReport);
    }

    public function testLeadReportWithDncListFilterNotIn(): void
    {
        $leads[] = $this->createContact('test1@example.com');
        $leads[] = $this->createContact('test2@example.com');
        $leads[] = $this->createContact('test3@example.com');
        $this->em->flush();

        $this->createDnc('email', $leads[0], DoNotContact::BOUNCED);
        $this->createDnc('email', $leads[1], DoNotContact::MANUAL);
        $this->createDnc('sms', $leads[2], DoNotContact::MANUAL);
        $this->em->flush();

        $report = $this->createReport(
            source: 'leads',
            columns: ['l.id', 'dnc_preferences'],
            filters: [
                [
                    'column'    => 'dnc_preferences',
                    'glue'      => 'and',
                    'dynamic'   => null,
                    'condition' => 'notIn',
                    'value'     => ['email:'.DoNotContact::BOUNCED], // Exclude bounced emails
                ],
            ],
            order: [['column' => 'l.id', 'direction' => 'ASC']]
        );

        $expectedReport = [
            [(string) $leads[1]->getId(), 'DNC Manually Unsubscribed: Email'],
            [(string) $leads[2]->getId(), 'DNC Manually Unsubscribed: Text Message'],
        ];
        $this->verifyReport($report->getId(), $expectedReport);
        $this->verifyApiReport($report->getId(), $expectedReport);
    }

    public function testLeadReportWithDncListFilterEmpty(): void
    {
        $leads[] = $this->createContact('test1@example.com');
        $leads[] = $this->createContact('test2@example.com');
        $leads[] = $this->createContact('test3@example.com');
        $this->em->flush();

        // Only add DNC for the first two contacts
        $this->createDnc('email', $leads[0], DoNotContact::BOUNCED);
        $this->createDnc('email', $leads[1], DoNotContact::MANUAL);
        $this->em->flush();

        $report = $this->createReport(
            source: 'leads',
            columns: ['l.id', 'dnc_preferences'],
            filters: [
                [
                    'column'    => 'dnc_preferences',
                    'glue'      => 'and',
                    'dynamic'   => null,
                    'condition' => 'empty',
                    'value'     => [], // Empty value as we're looking for contacts without DNC
                ],
            ],
            order: [['column' => 'l.id', 'direction' => 'ASC']]
        );

        $expectedReport = [
            [(string) $leads[2]->getId(), ''], // Only the contact without DNC
        ];
        $this->verifyReport($report->getId(), $expectedReport);
        $this->verifyApiReport($report->getId(), $expectedReport);
    }

    public function testLeadReportWithDncListFilterNotEmpty(): void
    {
        $leads[] = $this->createContact('test1@example.com');
        $leads[] = $this->createContact('test2@example.com');
        $leads[] = $this->createContact('test3@example.com');
        $this->em->flush();

        // Add DNC for the first two contacts
        $this->createDnc('email', $leads[0], DoNotContact::BOUNCED);
        $this->createDnc('email', $leads[1], DoNotContact::MANUAL);
        $this->em->flush();

        $report = $this->createReport(
            source: 'leads',
            columns: ['l.id', 'dnc_preferences'],
            filters: [
                [
                    'column'    => 'dnc_preferences',
                    'glue'      => 'and',
                    'dynamic'   => null,
                    'condition' => 'notEmpty',
                    'value'     => [], // Empty value as we're looking for any contacts with DNC
                ],
            ],
            order: [['column' => 'l.id', 'direction' => 'ASC']]
        );

        $expectedReport = [
            [(string) $leads[0]->getId(), 'DNC Bounced: Email'],
            [(string) $leads[1]->getId(), 'DNC Manually Unsubscribed: Email'],
        ];
        $this->verifyReport($report->getId(), $expectedReport);
        $this->verifyApiReport($report->getId(), $expectedReport);
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

    private function createContact(string $email): Lead
    {
        $lead = new Lead();
        $lead->setEmail($email);
        $this->em->persist($lead);

        return $lead;
    }
}
