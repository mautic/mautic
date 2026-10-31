<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\ReportBundle\Entity\Report;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ReportControllerTest extends MauticMysqlTestCase
{
    /**
     * Smoke test to ensure the '/s/reports' route loads.
     */
    public function testIndexRouteSuccessfullyLoads(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/reports');
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * Regression test for the is:mine report search command.
     */
    public function testIndexRouteSupportsIsMineSearch(): void
    {
        $currentUser = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertInstanceOf(User::class, $currentUser);

        $ownedReport = new Report();
        $ownedReport->setName('Owned is:mine report');
        $ownedReport->setSource('lead.pointlog');
        $ownedReport->setCreatedBy($currentUser);

        $otherReport = new Report();
        $otherReport->setName('Other user report');
        $otherReport->setSource('lead.pointlog');
        $otherReport->setCreatedBy(999999);

        $this->em->persist($ownedReport);
        $this->em->persist($otherReport);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/reports?search=is%3Amine');
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('Owned is:mine report', (string) $response->getContent());
        $this->assertStringNotContainsString('Other user report', (string) $response->getContent());
    }

    /**
     * Smoke test to ensure the '/s/reports/new' route loads.
     */
    public function testNewRouteSuccessfullyLoads(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/reports/new');
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }
}
