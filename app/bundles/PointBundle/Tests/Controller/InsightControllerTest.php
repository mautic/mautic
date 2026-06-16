<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PointBundle\Entity\PointInsight;
use Mautic\PointBundle\Model\InsightModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class InsightControllerTest extends MauticMysqlTestCase
{
    public function testInsightIndexActionWithoutPage(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/points/insights');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testInsightIndexActionWithPage(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/points/insights/1');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testInsightNewAction(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/points/insights/new');

        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
        $this->assertStringContainsString('New Point Insight', $crawler->filter('h1')->text());
    }

    public function testInsightEditAction(): void
    {
        $insight = $this->createTestInsight();
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/points/insights/edit/'.$insight->getId());

        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
        $this->assertStringContainsString('Edit Insight', $crawler->filter('h1')->text());
    }

    public function testInsightDeleteAction(): void
    {
        /** @var InsightModel $insightModel */
        $insightModel = self::getContainer()->get('mautic.point.model.insight');

        $insight = $this->createTestInsight();
        $this->em->flush();

        $insightId = $insight->getId();

        $this->assertNotNull($insightModel->getEntity($insightId));

        $this->client->request(Request::METHOD_POST, '/s/points/insights/delete/'.$insightId);

        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->isRedirect() || $response->isSuccessful(),
            'Expected redirect or success response, got: '.$response->getStatusCode()
        );

        $this->em->clear();
        $this->assertNull($insightModel->getEntity($insightId));
    }

    public function testInsightCloneAction(): void
    {
        /** @var InsightModel $insightModel */
        $insightModel = self::getContainer()->get('mautic.point.model.insight');

        $insightRepo = $insightModel->getRepository();

        $insight = $this->createTestInsight();
        $this->em->flush();
        $this->em->clear();

        $this->assertCount(1, $insightRepo->findAll());

        $crawler = $this->client->request(Request::METHOD_GET, '/s/points/insights/clone/'.$insight->getId());
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $form = $crawler->selectButton('Save')->form();
        $this->client->submit($form);

        $this->assertCount(2, $insightRepo->findAll());
    }

    public function testInsightBatchDeleteAction(): void
    {
        /** @var InsightModel $insightModel */
        $insightModel = self::getContainer()->get('mautic.point.model.insight');

        $insightRepo = $insightModel->getRepository();

        $insight1 = $this->createTestInsight('Test Insight 1');
        $insight2 = $this->createTestInsight('Test Insight 2');
        $this->em->flush();

        $this->assertCount(2, $insightRepo->findAll());

        $ids = json_encode([$insight1->getId(), $insight2->getId()]);

        $this->client->request(
            Request::METHOD_POST,
            '/s/points/insights/batchDelete?ids='.urlencode($ids)
        );

        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->isRedirect() || $response->isSuccessful(),
            'Expected redirect or success response, got: '.$response->getStatusCode()
        );

        $this->em->clear();
        $this->assertCount(0, $insightRepo->findAll());
    }

    private function createTestInsight(string $name = 'Test Insight'): PointInsight
    {
        $insight = new PointInsight();
        $insight->setName($name);
        $insight->setDescription('Test Description');
        $insight->setInsightType('compare_point_groups');
        $insight->setInsightAction('set_custom_field');
        $insight->setCustomField('test_field');
        $insight->setPointGroups([1, 2]);
        $insight->setIsPublished(true);

        $this->em->persist($insight);

        return $insight;
    }
}
