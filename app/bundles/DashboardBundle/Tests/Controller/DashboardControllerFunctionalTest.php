<?php

declare(strict_types=1);

namespace Mautic\DashboardBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\ReportBundle\Entity\Report;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;

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
        $this->em->clear();

        $this->client->request('GET', sprintf('/s/dashboard/widget/%s', $widget->getId()), [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response = $this->client->getResponse();
        Assert::assertSame(200, $response->getStatusCode());

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
}
