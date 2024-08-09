<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\ReportBundle\Entity\Report;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

class ReportSubscriberFunctionalTest extends MauticMysqlTestCase
{
    public function testEmailReportGraphWithMostClickedLinks(): void
    {
        $emailA = $this->createEmail('Email 1');
        $emailB = $this->createEmail('Email 2');
        $this->em->flush();

        $this->createTrackable('https://example.com/1', $emailA->getId(), 1, 1);
        $this->createTrackable('https://example.com/2', $emailA->getId(), 5, 2);
        $this->createTrackable('https://example.com/3', $emailB->getId());
        $this->createTrackable('https://example.com/4', $emailB->getId(), 2, 1);
        $this->createTrackable('https://example.com/5', $emailB->getId(), 10, 8);

        $this->em->flush();

        $report = new Report();
        $report->setName('Email stats and top 10 links');
        $report->setSource('emails');
        $report->setColumns(['e.id', 'cmp.name', 'e.name']);
        $report->setGraphs(['mautic.email.table.most.emails.clicks']);
        $this->em->persist($report);
        $this->em->flush();

        $crawler      = $this->client->request(Request::METHOD_GET, "/s/reports/view/{$report->getId()}");
        $this->assertTrue($this->client->getResponse()->isOk());
        $crawlerTable = $crawler->filterXPath('//*[contains(@href,"example.com")]')->closest('table');

        // convert html table to php array
        $table = array_slice($this->domTableToArray($crawlerTable), 1);

        $this->assertSame([
            ['Email 2', '10', '8', 'example.com/5'],
            ['Email 1', '5', '2', 'example.com/2'],
            ['Email 2', '2', '1', 'example.com/4'],
            ['Email 1', '1', '1', 'example.com/1'],
            ['Email 2', '0', '0', 'example.com/3'],
        ], $table);
    }

    public function testEmailStatReportGraphWithMostClickedLinks(): void
    {
        $email = $this->createEmail('Email');

        $contacts = [
            $this->createContact('test1@example.com'),
            $this->createContact('test2@example.com'),
            $this->createContact('test3@example.com'),
        ];
        $this->em->flush();

        $trackables = [
            $this->createTrackable('https://example.com/1', $email->getId()),
            $this->createTrackable('https://example.com/2', $email->getId()),
        ];
        $this->em->flush();

        $statsEmail = $this->emulateEmailSend($email, $contacts);

        $this->emulateEmailRead($statsEmail[0]);
        $this->emulateEmailRead($statsEmail[1]);

        $this->emulateLinkClick($email, $trackables[0], $contacts[0], 3);
        $this->emulateLinkClick($email, $trackables[1], $contacts[0]);
        $this->emulateLinkClick($email, $trackables[1], $contacts[1]);
        $this->em->flush();

        $report = new Report();
        $report->setName('Email sent stats with hits and top 10 links');
        $report->setSource('email.stats');
        $report->setColumns(['l.email', 'e.name', 'hits', 'unique_hits']);
        $report->setGraphs(['mautic.email.table.most.emails.clicks']);
        $report->setTableOrder([
            [
                'column'    => 'hits',
                'direction' => 'DESC',
            ],
        ]);
        $this->em->persist($report);
        $this->em->flush();

        $crawler            = $this->client->request(Request::METHOD_GET, "/s/reports/view/{$report->getId()}");
        $this->assertTrue($this->client->getResponse()->isOk());
        $crawlerReportTable = $crawler->filterXPath('//table[@id="reportTable"]')->first();
        $crawlerGraphTable  = $crawler->filterXPath('//*[contains(@href,"example.com")]')->closest('table');

        // convert html table to php array
        $crawlerReportTable = array_slice($this->domTableToArray($crawlerReportTable), 1, 3);
        $graphTableArray    = array_slice($this->domTableToArray($crawlerGraphTable), 1);

        $this->assertSame([
            ['1', 'test1@example.com', 'Email', '4', '2'],
            ['2', 'test2@example.com', 'Email', '1', '1'],
            ['3', 'test3@example.com', 'Email', '0', '0'],
        ], $crawlerReportTable);

        $this->assertSame([
            ['Email', '3', '1', 'example.com/1'],
            ['Email', '2', '2', 'example.com/2'],
        ], $graphTableArray);
    }

    public function testEmailReportWithClickThroughColumns(): void
    {
        $emails = [
            $this->createEmail('Email 1'),
            $this->createEmail('Email 2'),
        ];
        $this->em->flush();

        $contacts = [
            $this->createContact('test1@example.com'),
            $this->createContact('test2@example.com'),
            $this->createContact('test3@example.com'),
        ];
        $this->em->flush();

        $trackables = [
            [   // email 1
                $this->createTrackable('https://example.com/1', $emails[0]->getId()),
                $this->createTrackable('https://example.com/2', $emails[0]->getId()),
            ],
            [   // email 2
                $this->createTrackable('https://example.com/3', $emails[1]->getId()),
            ],
        ];
        $this->em->flush();

        $statsEmail = [
            $this->emulateEmailSend($emails[0], $contacts), // email 1
            $this->emulateEmailSend($emails[1], $contacts), // email 2
        ];

        $this->emulateEmailRead($statsEmail[0][0]); // email 1
        $this->emulateEmailRead($statsEmail[0][1]); // email 1
        $this->emulateEmailRead($statsEmail[1][2]); // email 2

        $this->emulateLinkClick($emails[0], $trackables[0][0], $contacts[0], 3); // email 1
        $this->emulateLinkClick($emails[0], $trackables[0][1], $contacts[0]);          // email 1
        $this->emulateLinkClick($emails[1], $trackables[1][0], $contacts[2]);          // email 2
        $this->em->flush();

        $report = new Report();
        $report->setName('Email with click through');
        $report->setSource('emails');
        $report->setColumns(['e.id', 'e.name', 'e.read_count', 'hits', 'click_through_count', 'click_through_rate', 'click_to_open_rate']);
        $this->em->persist($report);
        $this->em->flush();

        // -- test report table in mautic panel
        $crawler            = $this->client->request(Request::METHOD_GET, "/s/reports/view/{$report->getId()}");
        $this->assertTrue($this->client->getResponse()->isOk());
        $crawlerReportTable = $crawler->filterXPath('//table[@id="reportTable"]')->first();

        // convert html table to php array
        $crawlerReportTable = array_slice($this->domTableToArray($crawlerReportTable), 1, 2);

        $this->assertSame([
            // no., id, name, read_count, hits, click_through_count, click_through_rate, click_to_open_rate
            ['1', (string) $emails[0]->getId(), 'Email 1', '2', '4', '1', '33.3%', '50.0%'],
            ['2', (string) $emails[1]->getId(), 'Email 2', '1', '1', '1', '33.3%', '100.0%'],
        ], $crawlerReportTable);

        // -- test API report data
        $this->client->request(Request::METHOD_GET, "/api/reports/{$report->getId()}");
        $clientResponse = $this->client->getResponse();
        $result         = json_decode($clientResponse->getContent(), true);
        $this->assertSame([
            [
                'e_id'                => (string) $emails[0]->getId(),
                'e_name'              => 'Email 1',
                'read_count'          => '2',
                'hits'                => '4',
                'click_through_count' => '1',
                'click_through_rate'  => '33.3%',
                'click_to_open_rate'  => '50.0%',
            ],
            [
                'e_id'                => (string) $emails[1]->getId(),
                'e_name'              => 'Email 2',
                'read_count'          => '1',
                'hits'                => '1',
                'click_through_count' => '1',
                'click_through_rate'  => '33.3%',
                'click_to_open_rate'  => '100.0%',
            ],
        ], $result['data']);
    }

    private function createContact(string $email): Lead
    {
        $contact = new Lead();
        $contact->setEmail($email);
        $this->em->persist($contact);

        return $contact;
    }

    private function createEmail(string $name): Email
    {
        $email = new Email();
        $email->setName($name);
        $email->setSubject($name);
        $email->setEmailType('template');
        $email->setCustomHtml('<h1>Email content</h1><br>{signature}');
        $email->setIsPublished(true);
        $email->setFromAddress('from@example.com');
        $email->setFromName('Test');
        $this->em->persist($email);

        return $email;
    }

    private function createTrackable(string $url, int $channelId, int $hits = 0, int $uniqueHits = 0): Trackable
    {
        $redirect = new Redirect();
        $redirect->setRedirectId(uniqid());
        $redirect->setUrl($url);
        $redirect->setHits($hits);
        $redirect->setUniqueHits($uniqueHits);
        $this->em->persist($redirect);

        $trackable = new Trackable();
        $trackable->setChannelId($channelId);
        $trackable->setChannel('email');
        $trackable->setHits($hits);
        $trackable->setUniqueHits($uniqueHits);
        $trackable->setRedirect($redirect);
        $this->em->persist($trackable);

        return $trackable;
    }

    /**
     * @param Lead[] $contacts
     *
     * @return Stat[]
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function emulateEmailSend(Email $email, array $contacts): array
    {
        $stats = [];
        foreach ($contacts as $contact) {
            $emailStat = new Stat();
            $emailStat->setEmail($email);
            $emailStat->setEmailAddress($contact->getEmail());
            $emailStat->setLead($contact);
            $emailStat->setDateSent(new \DateTime());
            $this->em->persist($emailStat);
            $stats[] = $emailStat;
        }
        $email->setSentCount(count($contacts));
        $this->em->persist($email);

        $this->em->flush();

        return $stats;
    }

    private function emulateEmailRead(Stat $emailStat): void
    {
        $emailStat->setIsRead(true);
        $emailStat->setDateRead(new \DateTime());
        $emailStat->setOpenCount(1);
        $email = $emailStat->getEmail();
        $email->setReadCount($email->getReadCount() + 1);
        $this->em->persist($emailStat);
        $this->em->persist($email);
    }

    private function emulateLinkClick(Email $email, Trackable $trackable, Lead $lead, int $count = 1): void
    {
        $trackable->setHits($trackable->getHits() + $count);
        $trackable->setUniqueHits($trackable->getUniqueHits() + 1);
        $this->em->persist($trackable);

        $redirect = $trackable->getRedirect();

        $ip = new IpAddress();
        $ip->setIpAddress('127.0.0.1');
        $this->em->persist($ip);

        for ($i = 0; $i < $count; ++$i) {
            $pageHit = new Hit();
            $pageHit->setRedirect($redirect);
            $pageHit->setEmail($email);
            $pageHit->setLead($lead);
            $pageHit->setIpAddress($ip);
            $pageHit->setDateHit(new \DateTime());
            $pageHit->setCode(200);
            $pageHit->setUrl($redirect->getUrl());
            $pageHit->setTrackingId($redirect->getRedirectId());
            $pageHit->setSource('email');
            $pageHit->setSourceId($email->getId());
            $this->em->persist($pageHit);
        }
    }

    /**
     * @return array<int,array<int,mixed>>
     */
    private function domTableToArray(Crawler $crawler): array
    {
        return $crawler->filter('tr')->each(fn ($tr) => $tr->filter('td')->each(fn ($td) => trim($td->text())));
    }
}
