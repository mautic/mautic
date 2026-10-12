<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Page;
use Mautic\ProjectBundle\Entity\Project;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

final class PageControllerFunctionalTest extends MauticMysqlTestCase
{
    #[DataProvider('manualWinnerProvider')]
    public function testManualWinnerAction(bool $started, bool $published): void
    {
        $parent = $this->createPage();
        $parent->setTitle('Leading page');
        $parent->setAlias('leading-page');
        $variant = $this->createPage();
        $variant->setTitle('Other page');
        $variant->setAlias('other-page');
        $parent->setIsPublished(true);
        $parent->setVariantSettings(['weight' => 50, 'totalWeight' => 100, 'winnerCriteria' => 'page.dwelltime']);
        $parent->setVariantStartDate($started ? new \DateTime('2020-01-01', new \DateTimeZone('UTC')) : null);
        $variant->setIsPublished($published);
        $variant->setVariantParent($parent);
        $variant->setVariantSettings(['weight' => 50, 'winnerCriteria' => 'page.dwelltime']);
        $parent->addVariantChild($variant);
        foreach ([$parent, $variant] as $item) {
            $hit = new Hit();
            $hit->setPage($item);
            $hit->setCode(200);
            $hit->setDateHit(new \DateTime('2020-01-02 12:00:00', new \DateTimeZone('UTC')));
            $hit->setDateLeft(new \DateTime($item === $parent ? '2020-01-02 12:02:00' : '2020-01-02 12:00:10', new \DateTimeZone('UTC')));
            $hit->setTrackingId('manual-winner-'.$item->getId());
            $this->em->persist($hit);
        }
        $this->em->flush();
        $parentId = $parent->getId();
        $variantId = $variant->getId();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/pages/view/'.$parentId);
        $this->assertResponseIsSuccessful();
        $action = $crawler->filter('a[href="/s/pages/winner/'.$variantId.'"]');
        $this->assertCount($started && $published ? 1 : 0, $action);
        if (!$started || !$published) {
            return;
        }

        $this->assertCount(1, $action->filter('.ri-trophy-line'));
        $this->assertCount(1, $crawler->filter('a[href="/s/pages/winner/'.$parentId.'"] .ri-trophy-fill'));
        $this->assertSame('confirmation', $action->attr('data-toggle'));
        $this->assertSame('executeAction', $action->attr('data-confirm-callback'));
        $this->setCsrfHeader();
        $this->client->xmlHttpRequest(Request::METHOD_POST, $action->attr('href'));
        $this->assertResponseIsSuccessful();

        $this->em->clear();
        $winner = $this->em->find(Page::class, $variantId);
        $oldParent = $this->em->find(Page::class, $parentId);
        $this->assertInstanceOf(Page::class, $winner);
        $this->assertInstanceOf(Page::class, $oldParent);
        $this->assertNull($winner->getVariantParent());
        $this->assertTrue($winner->isPublished());
        $this->assertFalse($oldParent->isPublished());
        $this->assertSame($winner->getId(), $oldParent->getVariantParent()?->getId());
    }

    /**
     * @return iterable<string, array{bool, bool}>
     */
    public static function manualWinnerProvider(): iterable
    {
        yield 'published non-leading variant' => [true, true];
        yield 'test not started' => [false, true];
        yield 'unpublished variant' => [true, false];
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
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Test Html', (string) $response->getContent());
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
        $page->setTemplate('blank');
        $page->setCustomHtml('Test Html'.$token);
        $this->em->persist($page);
        $this->em->flush();

        return $page;
    }

    public function testPageWithProject(): void
    {
        $page = $this->createPage();

        $project = new Project();
        $project->setName('Test Project');
        $this->em->persist($project);

        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request('GET', '/s/pages/edit/'.$page->getId());
        $form    = $crawler->selectButton('Save')->form();
        $form['page[projects]']->setValue((string) $project->getId());

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $savedPage = $this->em->find(Page::class, $page->getId());
        $this->assertInstanceOf(Page::class, $savedPage);
        $this->assertSame($project->getId(), $savedPage->getProjects()->first()->getId());
    }

    public function testPageWithNullCustomHtmlIsUpdated(): void
    {
        $page = new Page();

        $page->setTitle('Page A');
        $page->setAlias('page-a');
        $page->setTemplate('mautic_code_mode');

        $this->em->persist($page);
        $this->em->flush();

        $pageId        = $page->getId();
        $crawler       = $this->client->request(Request::METHOD_GET, '/s/pages/edit/'.$pageId);
        $buttonCrawler = $crawler->selectButton('Save & Close');
        $form          = $buttonCrawler->form();

        $form['page[title]']->setValue('New Page');

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $this->em->clear();

        $this->assertEquals('New Page', $this->em->find(Page::class, $pageId)->getTitle());
    }

    public function testOptimisticLock(): void
    {
        $version = 1;
        $page    = $this->createPage();
        $this->em->flush();
        $this->assertPageVersion($page->getId(), $version);

        $crawler = $this->client->request('GET', '/s/pages/edit/'.$page->getId());
        $form    = $crawler->selectButton('Save')->form();
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->assertPageVersion($page->getId(), ++$version, 'The version should be incremented after submitting the form.');

        $form    = $crawler->selectButton('Save')->form();
        $crawler = $this->client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->assertPageVersion($page->getId(), $version, 'The version should stay the same as there was an optimistic lock error.');
        $this->assertStringContainsString('The record you are updating has been changed by someone else in the meantime. Please refresh the browser window and re-submit your changes.', $crawler->text(), 'There should be an optimistic error as the form was not refreshed after the previous submission.');
    }

    private function assertPageVersion(int $id, int $expectedVersion, string $message = ''): void
    {
        $this->em->clear();
        $page = $this->em->find(Page::class, $id);
        $this->assertInstanceOf(Page::class, $page);
        $this->assertSame($expectedVersion, $page->getVersion(), $message);
    }
}
