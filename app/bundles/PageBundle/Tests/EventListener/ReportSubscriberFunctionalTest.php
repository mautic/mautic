<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\EventListener;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\ReportBundle\Tests\Functional\AbstractReportSubscriberTestCase;

class ReportSubscriberFunctionalTest extends AbstractReportSubscriberTestCase
{
    public function testPageHitReportWithDncListColumn(): void
    {
        $leads[] = $this->createContact('test1@example.com');
        $leads[] = $this->createContact('test2@example.com');
        $leads[] = $this->createContact('test3@example.com');
        $this->em->flush();

        $this->createPageHit($leads[0], 2);
        $this->createPageHit($leads[1]);
        $this->createPageHit($leads[2], 1, 'https://mautic.org');

        $this->createDnc('email', $leads[0], DoNotContact::BOUNCED);
        $this->createDnc('email', $leads[1], DoNotContact::MANUAL);
        $this->createDnc('email', $leads[2], DoNotContact::UNSUBSCRIBED);
        $this->createDnc('sms', $leads[2], DoNotContact::MANUAL);
        $this->em->flush();

        $report = $this->createReport(
            source: 'page.hits',
            columns: ['l.id', 'ph.url', 'dnc_preferences'],
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
            // id, url, dnc_preferences
            [(string) $leads[0]->getId(), 'https://example.com', 'DNC Bounced: Email'],
            [(string) $leads[0]->getId(), 'https://example.com', 'DNC Bounced: Email'],
            [(string) $leads[2]->getId(), 'https://mautic.org', 'DNC Manually Unsubscribed: Text Message, DNC Unsubscribed: Email'],
        ];
        $this->verifyReport($report->getId(), $expectedReport);
        $this->verifyApiReport($report->getId(), $expectedReport);
    }

    private function createPageHit(Lead $lead, int $times = 1, string $url = 'https://example.com'): void
    {
        for ($i = 0; $i < $times; ++$i) {
            $pageHit = new Hit();
            $pageHit->setLead($lead);
            $pageHit->setDateHit(new \DateTime());
            $pageHit->setCode(200);
            $pageHit->setUrl($url);
            $pageHit->setTrackingId(substr(bin2hex(random_bytes(8)), 0, 16));
            $this->em->persist($pageHit);
        }
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
