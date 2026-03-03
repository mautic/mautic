<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\EventListener;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\ReportBundle\Tests\Functional\AbstractReportSubscriberTestCase;

class ReportSubscriberFunctionalTestCase extends AbstractReportSubscriberTestCase
{
    public function testPageHitReportWithTimeSpent(): void
    {
        $leads[] = $this->createContact('test1@example.com');
        $leads[] = $this->createContact('test2@example.com');
        $leads[] = $this->createContact('test3@example.com');
        $this->em->flush();

        // generate page hits for contacts
        $now = new \DateTime();
        $this->createPageHit($leads[0], 1, 'https://example.com/page1', (clone $now)->modify('-1 hour'), (clone $now)->modify('-55 minutes'));
        $this->createPageHit($leads[0], 1, 'https://example.com/page2', (clone $now)->modify('-50 minutes'), (clone $now)->modify('-49 minutes 59 seconds'));
        $this->createPageHit($leads[1], 1, 'https://example.com/page1', (clone $now)->modify('-40 minutes'));
        $this->createPageHit($leads[2], 1, 'https://example.com/page3', (clone $now)->modify('-30 minutes'), (clone $now)->modify('-25 minutes'));
        $this->em->flush();

        $report = $this->createReport(
            source: 'page.hits',
            columns: ['l.id', 'ph.url', 'ph.time_spent'],
            order: [['column' => 'l.id', 'direction' => 'ASC']]
        );

        $expectedReport = [
            // id, url, time_spent
            [(string) $leads[0]->getId(), 'https://example.com/page1', '00:05:00'],
            [(string) $leads[0]->getId(), 'https://example.com/page2', '00:01:59'],
            [(string) $leads[1]->getId(), 'https://example.com/page1', ''],
            [(string) $leads[2]->getId(), 'https://example.com/page3', '00:05:00'],
        ];
        $this->verifyReport($report->getId(), $expectedReport);
        $this->verifyApiReport($report->getId(), $expectedReport);
    }

    private function createPageHit(Lead $lead, int $times = 1, string $url = 'https://example.com', ?\DateTime $dateHit = null, ?\DateTime $dateLeft = null): void
    {
        for ($i = 0; $i < $times; ++$i) {
            $pageHit = new Hit();
            $pageHit->setLead($lead);
            $pageHit->setDateHit($dateHit);
            $pageHit->setDateLeft($dateLeft);
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
}
