<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Entity\Stat;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

final class SmsTranslationFunctionalTest extends MauticMysqlTestCase
{
    #[DataProvider('smsTimelineStatusProvider')]
    public function testSmsTimelineStatusIsTranslated(string $action, bool $isFailed, string $expectedString): void
    {
        $contact = new Lead();
        $contact->setFirstname('John');
        $contact->setLastname('Doe');
        $this->em->persist($contact);

        $sms = new Sms();
        $sms->setName('Test SMS');
        $sms->setMessage('Test message');
        $this->em->persist($sms);

        $stat = new Stat();
        $stat->setLead($contact);
        $stat->setSms($sms);
        $stat->setDateSent(new \DateTime('-1 hour'));
        $stat->setIsFailed($isFailed);
        $this->em->persist($stat);

        $log = new LeadEventLog();
        $log->setLead($contact);
        $log->setBundle('sms');
        $log->setObject('sms');
        $log->setAction($action);
        $log->setObjectId($sms->getId());
        $this->em->persist($log);

        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts/view/'.$contact->getId());
        $this->assertResponseIsSuccessful();

        $timelineTypeCrawler = $crawler->filter('tr.timeline-row > td.timeline-type');
        $this->assertGreaterThan(0, $timelineTypeCrawler->count(), 'No timeline events found');

        $found = false;
        foreach ($timelineTypeCrawler as $node) {
            if (str_contains($node->textContent, $expectedString)) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, "Expected string '{$expectedString}' not found in timeline types.");
    }

    /**
     * @return array<string, array{string, bool, string}>
     */
    public static function smsTimelineStatusProvider(): array
    {
        return [
            'sent'      => ['sent', false, 'Text Message Sent'],
            'failed'    => ['failed', true, 'Text Message Failed'],
        ];
    }
}
