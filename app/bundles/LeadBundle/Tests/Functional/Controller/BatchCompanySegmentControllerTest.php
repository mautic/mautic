<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BatchCompanySegmentControllerTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    public function testCompaniesAreAddedToThenRemovedFromSegmentsInBatch(): void
    {
        $segment    = $this->createCompanySegment('Tech Companies', 'tech-companies', true);
        $segmentId  = $segment->getId();
        $companyA   = $this->createCompany('Company A', 'a@example.com');
        $companyB   = $this->createCompany('Company B', 'b@example.com');
        $companyC   = $this->createCompany('Company C', 'c@example.com');

        $this->em->flush();
        $this->em->clear();

        $this->assertCount(0, $segment->getCompaniesSegments());

        $this->client->request(Request::METHOD_GET, '/s/company-segments/batch/company/view', [], [], $this->createAjaxHeaders());
        $this->assertResponseIsSuccessful();

        $clientResponse = $this->client->getResponse();
        $content        = $clientResponse->getContent();
        $this->assertNotFalse($content);

        $html = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($html);
        $this->assertIsString($html['newContent']);

        $crawler = new Crawler($html['newContent'], 'http://localhost/s/company-segments/batch/company/view');
        $form    = $crawler->filter('form[name=company_batch]')->form([
            'company_batch[add]' => [$segmentId],
        ]);

        $payload = $form->getPhpValues();
        $this->assertCount(1, $payload);
        $this->assertArrayHasKey('company_batch', $payload);
        $this->assertIsArray($payload['company_batch']);
        $this->assertCount(3, $payload['company_batch']);
        $this->assertArrayHasKey('_token', $payload['company_batch']);
        $this->assertArrayHasKey('add', $payload['company_batch']);
        $this->assertArrayHasKey('ids', $payload['company_batch']);

        $payload['company_batch']['ids'] = json_encode([$companyA->getId(), $companyB->getId(), $companyC->getId()], JSON_THROW_ON_ERROR);

        $this->client->request(Request::METHOD_POST, '/s/company-segments/batch/company/set', $payload);

        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());

        $this->em->clear();
        $segment = $this->em->getRepository(\Mautic\LeadBundle\Entity\CompanySegment::class)->find($segmentId);
        $this->assertNotNull($segment);

        $this->assertCount(3, $segment->getCompaniesSegments());

        $companiesSegments = $segment->getCompaniesSegments()->get(0);
        $this->assertNotNull($companiesSegments);
        $this->assertTrue($companiesSegments->isManuallyAdded());
        $this->assertFalse($companiesSegments->isManuallyRemoved());
        $this->assertSame($companyA->getId(), $companiesSegments->getCompany()->getId());

        $companiesSegments = $segment->getCompaniesSegments()->get(1);
        $this->assertNotNull($companiesSegments);
        $this->assertTrue($companiesSegments->isManuallyAdded());
        $this->assertFalse($companiesSegments->isManuallyRemoved());
        $this->assertSame($companyB->getId(), $companiesSegments->getCompany()->getId());

        $companiesSegments = $segment->getCompaniesSegments()->get(2);
        $this->assertNotNull($companiesSegments);
        $this->assertTrue($companiesSegments->isManuallyAdded());
        $this->assertFalse($companiesSegments->isManuallyRemoved());
        $this->assertSame($companyC->getId(), $companiesSegments->getCompany()->getId());

        $content = $clientResponse->getContent();
        $this->assertNotFalse($content);
        $response = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($response);
        $this->assertTrue(isset($response['closeModal']));
        $this->assertTrue($response['closeModal']);
        $this->assertIsString($response['flashes']);
        $this->assertStringContainsString('3 companies affected', $response['flashes']);

        $this->client->request(Request::METHOD_GET, '/s/company-segments/batch/company/view', [], [], $this->createAjaxHeaders());
        $this->assertResponseIsSuccessful();

        $clientResponse = $this->client->getResponse();
        $content        = $clientResponse->getContent();
        $this->assertNotFalse($content);

        $html = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($html);
        $this->assertIsString($html['newContent']);

        $crawler = new Crawler($html['newContent'], 'http://localhost/s/company-segments/batch/company/view');
        $form    = $crawler->filter('form[name=company_batch]')->form([
            'company_batch[remove]' => [$segmentId],
        ]);

        $payload = $form->getPhpValues();
        $this->assertIsArray($payload);
        $this->assertCount(1, $payload);
        $this->assertArrayHasKey('company_batch', $payload);
        $this->assertIsArray($payload['company_batch']);
        $this->assertCount(3, $payload['company_batch']);
        $this->assertArrayHasKey('remove', $payload['company_batch']);
        $this->assertArrayHasKey('_token', $payload['company_batch']);
        $this->assertArrayHasKey('ids', $payload['company_batch']);

        $payload['company_batch']['ids'] = json_encode([$companyA->getId(), $companyB->getId(), $companyC->getId()], JSON_THROW_ON_ERROR);

        $this->client->request(Request::METHOD_POST, '/s/company-segments/batch/company/set', $payload);

        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());

        $this->em->clear();
        $segment = $this->em->getRepository(\Mautic\LeadBundle\Entity\CompanySegment::class)->find($segmentId);
        $this->assertNotNull($segment);

        $this->assertCount(0, $segment->getCompaniesSegments());

        $content = $clientResponse->getContent();
        $this->assertNotFalse($content);
        $response = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($response);
        $this->assertTrue(isset($response['closeModal']));
        $this->assertTrue($response['closeModal']);
        $this->assertIsString($response['flashes']);
        $this->assertStringContainsString('3 companies affected', $response['flashes']);
    }
}
