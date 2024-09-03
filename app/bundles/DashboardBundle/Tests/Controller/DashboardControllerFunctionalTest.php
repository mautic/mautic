<?php

declare(strict_types=1);

namespace Mautic\DashboardBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\ReportBundle\Entity\Report;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;

class DashboardControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testWidgetWithReport(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy([]);

        $report = new Report();
        $report->setName('Lead and points');
        $report->setSource('lead.pointlog');
        $this->em->persist($report);
        $this->em->flush();

        $widget = new Widget();
        $widget->setName('Line graph report');
        $widget->setType('report');
        $widget->setParams(['graph' => sprintf('%s:mautic.lead.graph.line.leads', $report->getId())]);
        $widget->setWidth(100);
        $widget->setHeight(200);
        $widget->setCreatedBy($user);
        $this->em->persist($widget);

        $this->em->flush();
        $this->em->detach($widget);

        $this->client->request('GET', sprintf('/s/dashboard/widget/%s', $widget->getId()), [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response = $this->client->getResponse();
        self::assertResponseIsSuccessful();

        $content = $response->getContent();
        Assert::assertJson($content);

        $data = json_decode($content, true);
        Assert::assertIsArray($data);
        Assert::assertArrayHasKey('success', $data);
        Assert::assertSame(1, $data['success']);
        Assert::assertArrayHasKey('widgetId', $data);
        Assert::assertSame((string) $widget->getId(), $data['widgetId']);
        Assert::assertArrayHasKey('widgetWidth', $data);
        Assert::assertSame($widget->getWidth(), $data['widgetWidth']);
        Assert::assertArrayHasKey('widgetHeight', $data);
        Assert::assertSame($widget->getHeight(), $data['widgetHeight']);
        Assert::assertArrayHasKey('widgetHtml', $data);
        Assert::assertStringContainsString('View Full Report', $data['widgetHtml']);
    }

    public function testWidgetWithSegmentBuildTime(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy([]);
        $this->createSegment('A', 'a', 3, $user);
        $this->createSegment('B', 'b', 60, $user);
        $this->createSegment('C', 'c', 66, $user);
        $this->createSegment('D', 'd', 0.4, $user);

        $widget = new Widget();
        $widget->setName('Segments build time');
        $widget->setType('segments.build.time');
        $widget->setParams(['order' => 'DESC', 'segments' => []]);
        $widget->setWidth(100);
        $widget->setHeight(300);
        $widget->setCreatedBy($user);
        $this->em->persist($widget);

        $this->em->flush();
        $this->em->detach($widget);

        $this->client->request('GET', sprintf('/s/dashboard/widget/%s', $widget->getId()), [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response = $this->client->getResponse();
        self::assertResponseIsSuccessful();

        $content = $response->getContent();
        Assert::assertJson($content);

        $data = json_decode($content, true);
        Assert::assertIsArray($data);
        Assert::assertArrayHasKey('success', $data);
        Assert::assertSame(1, $data['success']);
        Assert::assertArrayHasKey('widgetHtml', $data);
        $tableArray = $this->widgetHtmlWithTableToArray($data['widgetHtml']);

        $this->assertSame([
            ['C', 'Admin User', '1 minute 6 seconds'],
            ['B', 'Admin User', '1 minute'],
            ['A', 'Admin User', '3 seconds'],
            ['D', 'Admin User', 'Less than 1 second'],
        ], $tableArray);
    }

    private function createSegment(string $name, string $alias, float $lastBuildTime = 0, ?User $user = null): LeadList
    {
        $segment = new LeadList();
        $segment->setName($name);
        $segment->setPublicName($name);
        $segment->setAlias($alias);
        $segment->setLastBuiltTime($lastBuildTime);

        if ($user) {
            $segment->setCreatedBy($user);
            $segment->setCreatedByUser($user->getName());
        }

        $this->em->persist($segment);

        return $segment;
    }

    /**
     * @return array<int,array<int,string>>
     */
    private function widgetHtmlWithTableToArray(string $widgetHtml): array
    {
        $doc = new \DOMDocument();
        $doc->loadHTML($widgetHtml);
        $crawler      = new Crawler($doc);
        $crawlerTable = $crawler->filter('table')->first();

        return array_slice($crawlerTable->filter('tr')->each(fn ($tr) => $tr->filter('td')->each(fn ($td) => trim($td->text()))), 1);
    }
}
