<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\CoreBundle\Entity\VariantEntityInterface;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\PageBundle\Entity\Page;
use Mautic\ProjectBundle\Entity\Project;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

final class PageControllerFunctionalTest extends MauticMysqlTestCase
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
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Test Html', (string) $response->getContent());
    }

    public function testManualWinnerActionIsAvailableForPublishedStartedVariant(): void
    {
        [$parent, $variant] = $this->createManualWinnerPagePair(true, true);
        $router             = self::getContainer()->get(RouterInterface::class);
        $winnerUrl          = $router->generate('mautic_page_action', [
            'objectAction' => 'winner',
            'objectId'     => $variant->getId(),
        ]);
        $viewUrl            = $router->generate('mautic_page_action', [
            'objectAction' => 'view',
            'objectId'     => $variant->getId(),
        ]);

        $this->client->request(Request::METHOD_GET, "/s/pages/view/{$parent->getId()}");
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('href="'.$winnerUrl.'"', (string) $this->client->getResponse()->getContent());

        $this->client->followRedirects(false);
        $this->client->request(Request::METHOD_POST, $winnerUrl);
        $this->assertResponseRedirects($viewUrl);

        $this->em->clear();
        $winner = $this->em->find(Page::class, $variant->getId());
        $oldParent = $this->em->find(Page::class, $parent->getId());

        $this->assertInstanceOf(Page::class, $winner);
        $this->assertInstanceOf(Page::class, $oldParent);
        $this->assertNotInstanceOf(VariantEntityInterface::class, $winner->getVariantParent());
        $this->assertTrue($winner->isPublished(false));
        $this->assertFalse($oldParent->isPublished(false));
        $this->assertSame(100, $winner->getVariantSettings()['totalWeight']);
    }

    public function testManualWinnerActionIsUnavailableForUnpublishedVariant(): void
    {
        [$parent, $variant] = $this->createManualWinnerPagePair(false, true);
        $winnerUrl          = self::getContainer()->get(RouterInterface::class)->generate('mautic_page_action', [
            'objectAction' => 'winner',
            'objectId'     => $variant->getId(),
        ]);

        $this->client->request(Request::METHOD_GET, "/s/pages/view/{$parent->getId()}");
        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('href="'.$winnerUrl.'"', (string) $this->client->getResponse()->getContent());
    }

    public function testManualWinnerActionIsUnavailableBeforeAbTestStarts(): void
    {
        [$parent, $variant] = $this->createManualWinnerPagePair(true, false);
        $winnerUrl          = self::getContainer()->get(RouterInterface::class)->generate('mautic_page_action', [
            'objectAction' => 'winner',
            'objectId'     => $variant->getId(),
        ]);

        $this->client->request(Request::METHOD_GET, "/s/pages/view/{$parent->getId()}");
        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('href="'.$winnerUrl.'"', (string) $this->client->getResponse()->getContent());
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
     * @return array{0: Page, 1: Page}
     */
    private function createManualWinnerPagePair(bool $variantPublished, bool $testStarted): array
    {
        $suffix = bin2hex(random_bytes(4));
        $parent = new Page();
        $parent->setTitle('Manual winner parent')
            ->setAlias('manual-winner-parent-'.$suffix)
            ->setTemplate('blank');
        $parent->setCustomHtml('Manual winner parent html');
        $parent
            ->setIsPublished(true)
            ->setVariantStartDate($testStarted ? new \DateTime('-1 hour') : null);
        $this->em->persist($parent);

        $variant = new Page();
        $variant->setTitle('Manual winner variant')
            ->setAlias('manual-winner-variant-'.$suffix)
            ->setTemplate('blank');
        $variant->setCustomHtml('Manual winner variant html');
        $variant
            ->setIsPublished($variantPublished)
            ->setVariantParent($parent)
            ->setVariantSettings([
                'weight'         => 50,
                'winnerCriteria' => 'page.bouncerate',
            ]);
        $parent->addVariantChild($variant);
        $this->em->persist($variant);
        $this->em->flush();

        return [$parent, $variant];
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
