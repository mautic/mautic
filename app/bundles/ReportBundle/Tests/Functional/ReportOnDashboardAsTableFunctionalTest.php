<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\EmailBundle\Entity\Email;
use Mautic\ReportBundle\Entity\Report;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\DomCrawler\Crawler;

final class ReportOnDashboardAsTableFunctionalTest extends MauticMysqlTestCase
{
    public function testReportOnDashboardAsTable(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy([]);

        $report = $this->createReport();
        $this->em->persist($report);
        $this->em->flush();

        // Create email
        $email  = $this->createEmail();
        $widget = $this->createWidget($user, $report);

        $this->em->persist($email);
        $this->em->persist($widget);
        $this->em->flush();

        $this->client->xmlHttpRequest('GET', '/s/dashboard/widget/'.$widget->getId());
        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $content  = $response->getContent();
        $this->assertJson($content);

        $data = json_decode($content, true);

        $crawler = new Crawler($data['widgetHtml']);

        $title   = $crawler->filter('.card-header h4')->text();
        $this->assertEquals('Emails Report: table', trim($title));

        $dropdownItems = $crawler->filter('.dropdown-menu li')->each(function ($node) {
            return trim($node->text());
        });

        $this->assertContains('Just retrieved latest data', $dropdownItems);
        $this->assertContains('Edit', $dropdownItems);
        $this->assertContains('Remove', $dropdownItems);

        $headers = $crawler->filter('table thead th')->each(function ($node) {
            return trim($node->text());
        });

        $expectedHeaders = ['Subject', 'Sent count', 'Read count', 'Read ratio', 'Unsubscribed ratio', 'Clicks ratio', 'Category name'];
        $this->assertEquals($expectedHeaders, $headers);

        $rows = $crawler->filter('table tbody tr');

        $this->assertCount(1, $rows);

        $columns = $rows->first()->filter('td')->each(function ($td) {
            return trim($td->text());
        });

        $expected = [
            $email->getSubject(),
            (string) $email->getSentCount(),
            (string) $email->getReadCount(),
            '50.0%',
            '0.0%',
            '0.0%',
            '',
        ];

        $this->assertEquals($expected, $columns);

        $link = $crawler->filter('.pull-right a')->attr('href');
        $this->assertEquals('/s/reports/view/'.$report->getId(), $link);
    }

    private function createReport(): Report
    {
        $report = new Report();
        $report->setName('All Emails');
        $report->setSource('emails');
        $report->setColumns(['e.subject', 'e.sent_count', 'e.read_count', 'read_ratio', 'unsubscribed_ratio', 'hits_ratio', 'c.title']);
        $report->setGraphs(['mautic.email.table.most.emails.table']);
        $report->setGroupBy(['e.id']);

        return $report;
    }

    private function createEmail(): Email
    {
        $email = new Email();
        $email->setName('Test Email');
        $email->setSubject('Test Email Subject');
        $email->setDescription('Test Email Description');
        $email->setSentCount(4);
        $email->setReadCount(2);
        $email->setIsPublished(true);

        return $email;
    }

    private function createWidget(User $user, Report $report): Widget
    {
        $widget = new Widget();
        $widget->setName('Emails Report: table');
        $widget->setType('report');
        $widget->setWidth(100);
        $widget->setHeight(330);
        $widget->setCreatedBy($user);
        $widget->setParams([
            'graph' => sprintf('%s:mautic.email.table.most.emails.table', $report->getId()),
        ]);

        return $widget;
    }
}
