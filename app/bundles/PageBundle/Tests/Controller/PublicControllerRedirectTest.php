<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PageBundle\Entity\Page;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicControllerRedirectTest extends MauticMysqlTestCase
{
    /**
     * @dataProvider redirectTypeOptions
     */
    public function testValidationRedirectWithoutUrl(string $redirectUrl, string $expectedMessage): void
    {
        $crawler    = $this->client->request(Request::METHOD_GET, '/s/pages/new');
        $saveButton = $crawler->selectButton('Save');
        $form       = $saveButton->form();
        $form['page[title]']->setValue('Redirect test');
        $form['page[redirectType]']->setValue((string) Response::HTTP_MOVED_PERMANENTLY);
        $form['page[redirectUrl]']->setValue($redirectUrl);
        $form['page[template]']->setValue('mautic_code_mode');

        $this->client->submit($form);

        Assert::assertStringContainsString($expectedMessage, $this->client->getResponse()->getContent());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public function redirectTypeOptions(): iterable
    {
        yield 'redirect set, empty redirect URL' => ['', 'A value is required.'];
        yield 'redirect set, invalid redirect URL' => ['invalid.url', 'This value is not a valid URL.'];
        yield 'redirect set, valid redirect URL' => ['https://valid.url', 'Edit Page - Redirect test'];
    }

    public function testCreateRedirectWithNoUrlForExistingPages(): void
    {
        $page = new Page();
        $page->setTitle('Page A');
        $page->setAlias('page-a');
        $page->setIsPublished(false);
        $page->setRedirectType((string) Response::HTTP_MOVED_PERMANENTLY);
        $this->em->persist($page);
        $this->em->flush();

        $this->logoutUser();

        $this->client->request(Request::METHOD_GET, '/page-a');

        Assert::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
