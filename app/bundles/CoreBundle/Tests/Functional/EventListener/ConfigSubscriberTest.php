<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

class ConfigSubscriberTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    protected string $prefix = '';

    protected function setUp(): void
    {
        $this->configParams['config_allowed_parameters'] = [
            'kernel.root_dir',
            'kernel.project_dir',
        ];

        $this->configParams['locale'] = 'en_US';

        parent::setUp();

        $this->prefix = MAUTIC_TABLE_PREFIX;

        $configPath = $this->getConfigPath();
        if (file_exists($configPath)) {
            // backup original local.php
            copy($configPath, $configPath.'.backup');
        } else {
            // write a temporary local.php
            file_put_contents($configPath, '<?php $parameters = [];');
        }
    }

    protected function beforeTearDown(): void
    {
        if (file_exists($this->getConfigPath().'.backup')) {
            // restore original local.php
            rename($this->getConfigPath().'.backup', $this->getConfigPath());
        } else {
            // local.php didn't exist to start with so delete
            unlink($this->getConfigPath());
        }
    }

    private function getConfigPath(): string
    {
        return self::getContainer()->get('kernel')->getLocalConfigFile();
    }

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

        $newFolder = $this->getContainer()->getParameter('mautic.image_path').'/../../media/newFolder';

        $crawler = $this->setImagePathRequest('media/newFolder');

        Assert::assertStringContainsString('The image path is invalid.', $crawler->text());

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
