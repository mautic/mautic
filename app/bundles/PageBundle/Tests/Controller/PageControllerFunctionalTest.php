<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\HttpFoundation\Request;

class PageControllerFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @dataProvider providePageRequestMethods
     */
    public function testPageRequestTracking(string $method, int $expectedCount): void
    {
        $page   = $this->createPage();
        $pageId = $page->getId();
        $url    = '/'.$page->getAlias();

        $this->client->request(Request::METHOD_GET, '/s/logout');
        $contactCount = $this->em->getRepository(Lead::class)->count([]);

        $this->client->request($method, $url, [], [], ['HTTP_USER_AGENT' => 'Mozilla/5.0']);

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertSame($contactCount + $expectedCount, $this->em->getRepository(Lead::class)->count([]));
        $this->assertSame($expectedCount, $this->em->getRepository(Hit::class)->count(['page' => $pageId]));
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function providePageRequestMethods(): iterable
    {
        yield 'HEAD does not track' => [Request::METHOD_HEAD, 0];
        yield 'GET still tracks' => [Request::METHOD_GET, 1];
    }

    public function testPagePreview(): void
    {
        $segment = $this->createSegment();
        $filter  = [
            [
                'glue'     => 'and',
                'field'    => 'leadlist',
                'object'   => 'lead',
                'type'     => 'leadlist',
                'filter'   => [$segment->getId()],
                'display'  => null,
                'operator' => 'in',
            ],
        ];
        $dynamicContent = $this->createDynamicContentWithSegmentFilter($filter);

        $dynamicContentToken = sprintf('{dwc=%s}', $dynamicContent->getSlotName());
        $page                = $this->createPage($dynamicContentToken);

        $this->client->request(Request::METHOD_GET, sprintf('/%s', $page->getAlias()));
        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Test Html', $response->getContent());
    }

    private function createSegment(): LeadList
    {
        $segment = new LeadList();
        $segment->setName('Segment 1');
        $segment->setPublicName('Segment 1');
        $segment->setAlias('segment_1');
        $this->em->persist($segment);
        $this->em->flush();

        return $segment;
    }

    /**
     * @param mixed[] $filters
     */
    private function createDynamicContentWithSegmentFilter(array $filters = []): DynamicContent
    {
        $dynamicContent = new DynamicContent();
        $dynamicContent->setName('DC 1');
        $dynamicContent->setDescription('Customised value');
        $dynamicContent->setFilters($filters);
        $dynamicContent->setIsCampaignBased(false);
        $dynamicContent->setSlotName('Segment1_Slot');
        $this->em->persist($dynamicContent);
        $this->em->flush();

        return $dynamicContent;
    }

    private function createPage(string $token = ''): Page
    {
        $page = new Page();
        $page->setIsPublished(true);
        $page->setTitle('Page Title');
        $page->setAlias('page-alias');
        $page->setTemplate('Blank');
        $page->setCustomHtml('Test Html'.$token);
        $this->em->persist($page);
        $this->em->flush();

        return $page;
    }
}
