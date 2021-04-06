<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Controller;

use DateTime;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\HttpFoundation\Request;

class PageControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testPageRedirection(): void
    {
        //create landing page
        $pageObject = new Page();
        $pageObject->setIsPublished(false);
        $pageObject->setDateAdded(new DateTime());
        $pageObject->setTitle('Page:Page:Redirection');
        $pageObject->setAlias('page-page-redirection');
        $pageObject->setTemplate('Blank');
        $pageObject->setCustomHtml('Test Html');
        $pageObject->setLanguage('en');
        $pageObject->setRedirectType(301);
        $pageObject->setRedirectUrl('https://www.google.com/');
        $this->em->persist($pageObject);
        $this->em->flush();

        // list landing page
        $crawler = $this->client->request(Request::METHOD_GET, '/s/pages');

        // check added landing page is listed or not
        $this->assertStringContainsString('Page:Page:Redirection (page-page-redirection)', $crawler->filterXPath('//*[@id="pageTable"]/tbody/tr[1]/td[2]/a')->text());

        // check page content if logged-in user accessed the landing page
        $redirectPageContent = $this->client->request(Request::METHOD_GET, '/page-page-redirection');
        $this->assertStringContainsString('Test Html', $redirectPageContent->text());

        // Logout and visit the landing page. It should now be redirected as per the configuration.
        $this->client->request(Request::METHOD_GET, '/s/logout');
        $this->client->followRedirects(false);
        $this->client->request(Request::METHOD_GET, '/page-page-redirection');

        $this->markTestSkipped('There is a bug currently.');

        // Check response if it is redirected as per the configuration.
        $response = $this->client->getResponse();
        $this->assertSame($pageObject->getRedirectType(), $response->getStatusCode());
        $this->assertSame($pageObject->getRedirectUrl(), $response->headers->get('Location'));
    }

    public function testPagePreview(): void
    {
        $segment        = $this->createSegment();
        $filter         = [
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
        $segment->setAlias('segment_1');
        $this->em->persist($segment);
        $this->em->flush();

        return $segment;
    }

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
