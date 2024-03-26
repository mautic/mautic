<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\HttpFoundation\Request;

class PageControllerFunctionalTest extends MauticMysqlTestCase
{
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
