<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PageBundle\Entity\Page;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class NotFoundFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testCustom404Page(): void
    {
        // Create a custom 404 page:
        $notFoundPage = new Page();
        $notFoundPage->setTitle('404 Not Found');
        $notFoundPage->setAlias('404-not-found');
        $notFoundPage->setCustomHtml('<html><body>Custom 404 Not Found Page</body></html>');

        $this->em->persist($notFoundPage);
        $this->em->flush();

        // Configure the 404 page:
        $this->configParams['404_page'] = $notFoundPage->getId();
        parent::setUpSymfony($this->configParams);

        // Test the custom 404 page:
        $crawler = $this->client->request(Request::METHOD_GET, '/page-that-does-not-exist');
        Assert::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        Assert::assertStringContainsString('Custom 404 Not Found Page', $crawler->text());
        Assert::assertFalse($this->client->getResponse()->isRedirection(), 'The response should not be a redirect.');
        Assert::assertSame('/page-that-does-not-exist', $this->client->getRequest()->getRequestUri(), 'The request URI should be the same as the original URI.');
    }
}
