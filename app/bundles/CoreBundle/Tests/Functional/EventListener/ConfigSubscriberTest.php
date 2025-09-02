<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

class ConfigSubscriberTest extends MauticMysqlTestCase
{
    public function testFailConfigMediaPathWithDots(): void
    {
        $crawler = $this->setImagePathRequest('media/..');
        Assert::assertStringContainsString('The image path is invalid.', $crawler->text());

        $crawler = $this->setImagePathRequest('..');
        Assert::assertStringContainsString('The image path is invalid', $crawler->text());

        $crawler = $this->setImagePathRequest('...');
        Assert::assertStringContainsString('The image path is invalid.', $crawler->text());

        $crawler = $this->setImagePathRequest('./');
        Assert::assertStringContainsString('The image path is invalid.', $crawler->text());

        $crawler = $this->setImagePathRequest('../');
        Assert::assertStringContainsString('The image path is invalid.', $crawler->text());

        $crawler = $this->setImagePathRequest('./../');
        Assert::assertStringContainsString('The image path is invalid.', $crawler->text());
    }

    public function testFailConfigMediaPathWithSystemDirectories(): void
    {
        $crawler = $this->setImagePathRequest('app/');
        Assert::assertStringContainsString('The image path is invalid.', $crawler->text());

        $crawler = $this->setImagePathRequest('app\\');
        Assert::assertStringContainsString('The image path is invalid.', $crawler->text());

        $crawler = $this->setImagePathRequest('app\\..');
        Assert::assertStringContainsString('The image path is invalid.', $crawler->text());

        $crawler = $this->setImagePathRequest('app/../');
        Assert::assertStringContainsString('The image path is invalid.', $crawler->text());

        $crawler = $this->setImagePathRequest('app\\../');
        Assert::assertStringContainsString('The image path is invalid.', $crawler->text());

        $crawler = $this->setImagePathRequest('app\\..\\');
        Assert::assertStringContainsString('The image path is invalid.', $crawler->text());

        $crawler = $this->setImagePathRequest('bin');
        Assert::assertStringContainsString('The image path is invalid.', $crawler->text());

        $crawler = $this->setImagePathRequest('bin/');
        Assert::assertStringContainsString('The image path is invalid.', $crawler->text());

        $crawler = $this->setImagePathRequest('themes');
        Assert::assertStringContainsString('The image path is invalid.', $crawler->text());
    }

    public function testFoldersThatDontExist(): void
    {
        $crawler = $this->setImagePathRequest('media/this-folder-does-not-exist');
        Assert::assertStringContainsString('The image path is invalid.', $crawler->text());

        $crawler = $this->setImagePathRequest('media/this-folder-does-not-exist/');
        Assert::assertStringContainsString('The image path is invalid.', $crawler->text());

        $crawler = $this->setImagePathRequest('media/this-folder-does-not-exist/this-folder-does-not-exist');
        Assert::assertStringContainsString('The image path is invalid.', $crawler->text());

        $crawler = $this->setImagePathRequest('media/this-folder-does-not-exist/this-folder-does-not-exist/');
        Assert::assertStringContainsString('The image path is invalid.', $crawler->text());
    }

    public function testValidFolders(): void
    {
        $crawler = $this->setImagePathRequest('media/');
        Assert::assertStringNotContainsString('The image path is invalid.', $crawler->text());

        $crawler = $this->setImagePathRequest('media/files/');
        Assert::assertStringNotContainsString('The image path is invalid.', $crawler->text());

        $newFolder = $this->getContainer()->getParameter('mautic.media_path').'/../media/newFolder';

        if (!file_exists($newFolder)) {
            mkdir($newFolder, 0777, true);
        }

        $crawler = $this->setImagePathRequest('media/newFolder');
        Assert::assertStringNotContainsString('The image path is invalid.', $crawler->text());
        if (is_dir($newFolder)) {
            rmdir($newFolder);
        }
    }

    private function setImagePathRequest(string $value): Crawler
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit');
        Assert::assertTrue($this->client->getResponse()->isOk());

        // Find save & close button
        $buttonCrawler = $crawler->selectButton('config[buttons][save]');
        $form          = $buttonCrawler->form();
        $form->setValues(
            [
                'config[coreconfig][site_url]'                    => 'https://mautic-community.local', // required
                'config[leadconfig][contact_columns]'             => ['name', 'email', 'id'],
                'config[coreconfig][image_path]'                  => $value,
            ]
        );

        $crawler = $this->client->submit($form);
        Assert::assertSame(200, $this->client->getResponse()->getStatusCode());

        return $crawler;
    }
}
