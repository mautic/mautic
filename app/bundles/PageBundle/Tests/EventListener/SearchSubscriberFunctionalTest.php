<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\EventListener;

use Generator;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchSubscriberFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @dataProvider dataProvider
     *
     * @param array<string, string|bool> $value
     */
    public function testOnGlobalSearch(array $value, string $expected): void
    {
        $page = $this->createTestPage($value);

        $this->client->request(Request::METHOD_GET, 's/ajax?action=globalSearch&global_search='.$page->getTitle().'&tmpl=list');
        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $responseContent = json_decode($response->getContent(), true);
        $this->assertStringContainsString($expected, $responseContent['newContent'], 'The page was not found');
    }

    /**
     * @param array<string, string|bool> $params
     */
    private function createTestPage(array $params = []): Page
    {
        $page = new Page();

        $title       = $params['title'] ?? 'Test Page';
        $alias       = $params['alias'] ?? 'test-page';
        $description = $params['description'] ?? 'This is a landing page';
        $isPublished = $params['isPublished'] ?? true;
        $template    = $params['template'] ?? 'blank';
        $customHtml  = $params['customHtml'] ?? '<!DOCTYPE html><html><head></head><body></body></html>';

        $page->setTitle($title);
        $page->setAlias($alias);
        $page->setIsPublished($isPublished);
        $page->setMetaDescription($description);
        $page->setTemplate($template);
        $page->setCustomHtml($customHtml);

        $this->em->persist($page);
        $this->em->flush();

        return $page;
    }

    /**
     * @return Generator<array<int, array<string, string|bool>|string>>
     */
    public function dataProvider(): Generator
    {
        yield [
            [
                'title'       => 'Page 1',
                'alias'       => 'page-1',
                'description' => 'The first landing page',
            ],
            'Page 1',
        ];
        yield [
            [
                'title'       => 'Page 2',
                'alias'       => 'page-2',
                'description' => 'The second landing page',
            ],
            'Page 2',
        ];
    }
}
