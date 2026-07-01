<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageEvent;
use Mautic\PageBundle\PageEvents;
use PHPUnit\Framework\Assert;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

final class AjaxControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testGetBuilderTokensAction(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/ajax?action=page:getBuilderTokens');
        self::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        Assert::assertArrayHasKey('tokens', $response);
        Assert::assertArrayHasKey('{pagetitle}', $response['tokens']);
        Assert::assertArrayHasKey('{langbar}', $response['tokens']);
        Assert::assertArrayHasKey('{today}', $response['tokens']);
    }

    public function testTogglePublishEventIsDispatched(): void
    {
        $dispatchedEvent = null;

        self::getContainer()
            ->get(EventDispatcherInterface::class)
            ->addListener(PageEvents::PAGE_ON_TOGGLE_PUBLISH, function (PageEvent $event) use (&$dispatchedEvent): void {
                $dispatchedEvent = $event;
            });

        $page = new Page();
        $page->setTitle('TestPage');
        $page->setAlias($page->getTitle());
        $page->setIsPublished(true);
        $this->em->persist($page);
        $this->em->flush();
        $this->em->clear();

        $this->client->request(Request::METHOD_POST, '/s/ajax', [
            'action' => 'togglePublishStatus',
            'model'  => 'page',
            'id'     => $page->getId(),
        ]);
        $this->assertResponseIsSuccessful();

        $page = $this->em->getRepository(Page::class)->find($page->getId());
        Assert::assertFalse($page->isPublished(), 'The page should not be published.');
        Assert::assertInstanceOf(PageEvent::class, $dispatchedEvent, 'The event should have been dispatched.');
        Assert::assertSame($page->getId(), $dispatchedEvent->getPage()->getId(), 'The page entity should match the one in the request.');
    }
}
