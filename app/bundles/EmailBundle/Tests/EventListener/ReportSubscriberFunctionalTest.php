<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\ReportBundle\Entity\Report;
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

        $report = $this->createReport(
            'Emails and top 10 links',
            'emails',
            ['e.id', 'e.name'],
            ['mautic.email.table.most.emails.clicks']
        );
        $this->em->flush();

        $crawler      = $this->client->request(Request::METHOD_GET, "/s/reports/view/{$report->getId()}");
        $crawlerTable = $crawler->filterXPath('//*[contains(@href,"example.com")]')->closest('table');

        // convert html table to php array
        $table = array_slice($crawlerTable->filter('tr')->each(function ($tr) {
            return $tr->filter('td')->each(function ($td) {
                return trim($td->text());
            });
        }), 1);

        $this->assertSame([
            ['Email 2', '10', '8', 'example.com/5'],
            ['Email 1', '5', '2', 'example.com/2'],
            ['Email 2', '2', '1', 'example.com/4'],
            ['Email 1', '1', '1', 'example.com/1'],
            ['Email 2', '0', '0', 'example.com/3'],
        ], $table);
    }

    private function createReport(string $name, string $source, array $columns, array $graphs = []): Report
    {
        $report = new Report();
        $report->setName($name);
        $report->setSource($source);
        $report->setColumns($columns);
        $report->setGraphs($graphs);
        $this->em->persist($report);

        return $report;
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
}
