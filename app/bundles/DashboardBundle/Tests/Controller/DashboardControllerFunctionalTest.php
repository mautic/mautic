<?php

declare(strict_types=1);

namespace Mautic\DashboardBundle\Tests\Controller;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\ReportBundle\Entity\Report;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\FileFormField;
use Symfony\Component\HttpFoundation\Request;

final class DashboardControllerFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    public function testUploadedDashboardIsPreviewed(): void
    {
        $dashboardName       = 'Automation test dashboard preview';
        $dashboardFileName   = $dashboardName.'.json';
        $temporaryDirectory  = sys_get_temp_dir().'/mautic-dashboard-'.bin2hex(random_bytes(6));
        $this->assertTrue(mkdir($temporaryDirectory));
        $filePath = $temporaryDirectory.'/'.$dashboardFileName;
        file_put_contents($filePath, json_encode([
            'name'    => $dashboardName,
            'widgets' => [],
        ], JSON_THROW_ON_ERROR));

        try {
            $crawler = $this->client->request(Request::METHOD_GET, '/s/dashboard/import');
            $this->assertResponseIsSuccessful();
            $this->assertSelectorExists('form[name="dashboard_upload"]');
            $form = $crawler->selectButton('Upload')->form();
            $fileField = $form['dashboard_upload[file]'];
            $this->assertInstanceOf(FileFormField::class, $fileField);
            $fileField->upload($filePath);
            $this->client->submit($form);

            $this->assertResponseIsSuccessful();
            $this->assertSelectorExists('.list-group-item.active');
            $this->assertSelectorTextContains('.list-group-item.active', $dashboardName);
        } finally {
            $parameters             = self::getContainer()->get(CoreParametersHelper::class);
            $dashboardDirectory     = (string) $parameters->get('dashboard_import_user_dir');
            $uploadedDashboards     = [$dashboardDirectory.'/'.$dashboardFileName];
            $userUploadedDashboards = glob($dashboardDirectory.'/*/'.$dashboardFileName);
            if (false !== $userUploadedDashboards) {
                $uploadedDashboards = array_merge($uploadedDashboards, $userUploadedDashboards);
            }

            foreach ($uploadedDashboards as $uploadedDashboard) {
                if (file_exists($uploadedDashboard)) {
                    unlink($uploadedDashboard);
                }
            }
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            if (is_dir($temporaryDirectory)) {
                rmdir($temporaryDirectory);
            }
        }
    }

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
        $this->assertInstanceOf(User::class, $user);
        $widget->setCreatedBy($user);
        $this->em->persist($widget);

        $this->em->flush();
        $this->em->detach($widget);

        $this->client->xmlHttpRequest('GET', sprintf('/s/dashboard/widget/%s', $widget->getId()));
        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        self::assertResponseIsSuccessful();

        $content = $response->getContent();
        $this->assertJson($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertSame(1, $data['success']);
        $this->assertArrayHasKey('widgetId', $data);
        $this->assertSame((string) $widget->getId(), $data['widgetId']);
        $this->assertArrayHasKey('widgetWidth', $data);
        $this->assertSame($widget->getWidth(), $data['widgetWidth']);
        $this->assertArrayHasKey('widgetHeight', $data);
        $this->assertSame($widget->getHeight(), $data['widgetHeight']);
        $this->assertArrayHasKey('widgetHtml', $data);
        $this->assertStringContainsString('View Full Report', (string) $data['widgetHtml']);
    }

    public function testWidgetWithBestHours(): void
    {
        $user    = $this->em->getRepository(User::class)->findOneBy([]);
        $segment = $this->createSegment('A', 'a');
        $widget  = new Widget();
        $widget->setName('Best email read hours');
        $widget->setType('emails.best.hours');
        $widget->setParams(['timeFormat' => 24, 'segmentId' => $segment->getId()]);
        $widget->setWidth(100);
        $widget->setHeight(200);
        $this->assertInstanceOf(User::class, $user);
        $widget->setCreatedBy($user);
        $this->em->persist($widget);

        $this->em->flush();
        $this->em->detach($widget);

        $this->client->xmlHttpRequest('GET', "/s/dashboard/widget/{$widget->getId()}");
        $this->assertResponseIsSuccessful();

        $content = $this->client->getResponse()->getContent();
        $this->assertJson($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertSame(1, $data['success']);
        $this->assertArrayHasKey('widgetId', $data);
        $this->assertSame((string) $widget->getId(), $data['widgetId']);
        $this->assertArrayHasKey('widgetWidth', $data);
        $this->assertSame($widget->getWidth(), $data['widgetWidth']);
        $this->assertArrayHasKey('widgetHeight', $data);
        $this->assertSame($widget->getHeight(), $data['widgetHeight']);
        $this->assertArrayHasKey('widgetHtml', $data);
        $this->assertStringContainsString('Best email read hours', (string) $data['widgetHtml']);
    }

    public function testWidgetWithSegmentBuildTime(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy([]);
        $this->assertInstanceOf(User::class, $user);
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

        $this->client->xmlHttpRequest('GET', sprintf('/s/dashboard/widget/%s', $widget->getId()));
        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        self::assertResponseIsSuccessful();

        $content = $response->getContent();
        $this->assertJson($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertSame(1, $data['success']);
        $this->assertArrayHasKey('widgetHtml', $data);
        $tableArray = $this->widgetHtmlWithTableToArray($data['widgetHtml']);

        $this->assertSame([
            ['C', 'Admin User', '1 minute 6 seconds'],
            ['B', 'Admin User', '1 minute'],
            ['A', 'Admin User', '3 seconds'],
            ['D', 'Admin User', 'Less than 1 second'],
        ], $tableArray);
    }

    public function testAuditLogWidgetWithDeletedContact(): void
    {
        $user   = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $widget = new Widget();
        $widget->setName('Recent activity');
        $widget->setType('recent.activity');
        $widget->setWidth(100);
        $widget->setHeight(300);
        $this->assertInstanceOf(User::class, $user);
        $widget->setCreatedBy($user);
        $this->em->persist($widget);
        $this->em->flush();
        $contact = new Lead();
        $contact->setFirstName('John');
        /** @var LeadModel $contactModel */
        $contactModel = self::getContainer()->get(LeadModel::class);
        $this->assertInstanceOf(LeadModel::class, $contactModel);
        $contactModel->saveEntity($contact);
        $contactModel->deleteEntity($contact);
        $this->em->clear();
        $this->client->xmlHttpRequest(Request::METHOD_GET, "/s/dashboard/widget/{$widget->getId()}");
        $this->assertResponseIsSuccessful();
        $printResponse = fn (): string => print_r(json_decode($this->client->getResponse()->getContent(), true), true);
        $this->assertStringContainsString('created', $printResponse());
        $this->assertStringContainsString('deleted', $printResponse());
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

        return array_slice($crawlerTable->filter('tr')->each(fn ($tr) => $tr->filter('td')->each(fn ($td): string => trim($td->text()))), 1);
    }

    public function testUpcomingEmailsWidget(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);

        $widget = new Widget();
        $widget->setName('Upcoming Emails');
        $widget->setType('upcoming.emails');
        $widget->setWidth(50);
        $widget->setHeight(330);
        $this->assertInstanceOf(User::class, $user);
        $widget->setCreatedBy($user);

        $this->em->persist($widget);
        $this->em->flush();

        $campaign = $this->createCampaign('Test Campaign');
        $event    = $this->createEvent(
            'Send Email',
            $campaign,
            'email.send',
            'action',
            ['email' => 1]
        );

        $lead = $this->createLead('TestFN', 'TestLN');

        $campaignLeadEvent = new LeadEventLog();
        $campaignLeadEvent->setLead($lead);
        $campaignLeadEvent->setEvent($event);
        $campaignLeadEvent->setCampaign($campaign);
        $campaignLeadEvent->setTriggerDate(new \DateTime('+1 day'));
        $campaignLeadEvent->setIsScheduled(true);
        $this->em->persist($campaignLeadEvent);

        $this->em->flush();

        $this->client->request('GET', "/s/dashboard/widget/{$widget->getId()}", [], [], $this->createAjaxHeaders());

        self::assertResponseIsSuccessful();
        $this->assertStringContainsString('TestFN TestLN', (string) $this->client->getResponse()->getContent());
    }
}
