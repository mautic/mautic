<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Entity\PageDraft;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class PageDraftFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        // Don't automatically load symfony because these tests need to set custom config parameters
    }

    public function testPageDraftNotConfigured(): void
    {
        $page    = $this->createNewPage('blank', 'Test html', false);
        $crawler = $this->client->request(Request::METHOD_GET, "/s/pages/edit/{$page->getId()}");
        Assert::assertEquals(0, $crawler->selectButton('Save as Draft')->count());
        Assert::assertEquals(0, $crawler->selectButton('Apply Draft')->count());
        Assert::assertEquals(0, $crawler->selectButton('Discard Draft')->count());
    }

    public function testPageDraftConfigured(): void
    {
        $page    = $this->createNewPage('blank', 'Test html', true);
        $crawler = $this->client->request(Request::METHOD_GET, "/s/pages/edit/{$page->getId()}");

        Assert::assertEquals(1, $crawler->selectButton('Save as Draft')->count());
        Assert::assertEquals(0, $crawler->selectButton('Apply Draft')->count());
        Assert::assertEquals(0, $crawler->selectButton('Discard Draft')->count());
    }

    public function testCheckDraftInList(): void
    {
        $page    = $this->createNewPage('blank', 'Test html', true);
        $crawler = $this->client->request(Request::METHOD_GET, '/s/pages');
        $this->assertStringNotContainsString('Has draft', $crawler->filter('#app-content a[href="/s/pages/view/'.$page->getId().'"]')->html());
        $this->saveDraft($page);
        $crawler = $this->client->request(Request::METHOD_GET, '/s/pages');
        $this->assertStringContainsString('Has draft', $crawler->filter('#app-content a[href="/s/pages/view/'.$page->getId().'"]')->html());
    }

    public function testPreviewDraft(): void
    {
        $page = $this->createNewPage('blank', 'Test html', true);
        $this->saveDraft($page);
        $crawler = $this->client->request(Request::METHOD_GET, "/page/preview/{$page->getId()}");
        $this->assertEquals('Test html', $crawler->text());

        $crawler = $this->client->request(Request::METHOD_GET, "/page/preview/{$page->getId()}/draft");
        $this->assertEquals('Test html Draft', $crawler->text());
    }

    public function testSaveDraftAndApplyDraft(): void
    {
        $page = $this->createNewPage('blank', 'Test html', true);
        $this->saveDraft($page);
        $this->applyDraft($page);
    }

    public function testDiscardDraft(): void
    {
        $page = $this->createNewPage('blank', 'Test html', true);
        $this->saveDraft($page);
        $this->discardDraft($page);
    }

    private function applyDraft(Page $page): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, "/s/pages/edit/{$page->getId()}");
        $form    = $crawler->selectButton('Apply Draft')->form();
        $this->client->submit($form);
        Assert::assertTrue($this->client->getResponse()->isOk());

        $pageDraft = $this->em->getRepository(PageDraft::class)->findOneBy(['page' => $page]);

        Assert::assertNull($pageDraft);
        Assert::assertSame('Test html Draft', $page->getCustomHtml());
    }

    private function discardDraft(Page $page): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, "/s/pages/edit/{$page->getId()}");
        $form    = $crawler->selectButton('Discard Draft')->form();
        $this->client->submit($form);
        Assert::assertTrue($this->client->getResponse()->isOk());

        $pageDraft = $this->em->getRepository(PageDraft::class)->findOneBy(['page' => $page]);

        Assert::assertNull($pageDraft);
        Assert::assertSame('Test html', $page->getCustomHtml());
    }

    private function saveDraft(Page $page): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, "/s/pages/edit/{$page->getId()}");

        $form                          = $crawler->selectButton('Save as Draft')->form();
        $form['page[customHtml]']      = 'Test html Draft';
        $this->client->submit($form);
        Assert::assertTrue($this->client->getResponse()->isOk());

        $pageDraft = $this->em->getRepository(PageDraft::class)->findOneBy(['page' => $page]);
        Assert::assertEquals('Test html Draft', $pageDraft->getHtml());
        Assert::assertSame('Test html', $page->getCustomHtml());
    }

    private function createNewPage($templateName, $templateContent, $isDraftEnabled): Page
    {
        $this->setConfig($isDraftEnabled);
        $date       = (new \DateTime())->format('Y-m-d H:i:s');
        $pageObject = new Page();
        $pageObject->setIsPublished(true);
        $pageObject->setDateAdded($date);
        $pageObject->setTitle('Page Test');
        $pageObject->setAlias('Page Test');
        $pageObject->setTemplate($templateName);
        $pageObject->setCustomHtml($templateContent);
        $pageObject->setLanguage('en');
        $this->em->persist($pageObject);
        $this->em->flush();

        return $pageObject;
    }

    private function setConfig($isDraftEnabled): void
    {
        $this->setUpSymfony(
            [
                'page_draft_enabled' => $isDraftEnabled,
            ]
        );
    }
}
