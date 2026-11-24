<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\DependencyInjection\Compiler;

use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

final class SystemThemeTemplatePathPassTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->createOverrideFile();

        // This test require cache to be cleared
        // as the template override must exist before the cache is generated.
        $pathsHelper = $this->getService(PathsHelper::class);
        $cacheDir    = $pathsHelper->getCachePath();

        $filesystem = new Filesystem();

        try {
            // Delete the cache directory
            $filesystem->remove($cacheDir);
        } catch (IOExceptionInterface $ioException) {
            echo 'An error occurred while deleting the cache directory at '.$ioException->getPath();
        }

        // Assert that the cache directory no longer exists
        $this->assertDirectoryDoesNotExist($cacheDir);

        parent::setUp();
    }

    public function testUserProfilePageOverrideFromSystemThemDirectory(): void
    {
        $this->assertFileExists($this->getOverridePath().'/index.html.twig');

        $this->client->request(Request::METHOD_GET, '/s/account');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Override test', (string) $this->client->getResponse()->getContent(), 'Page has not override.');
    }

    protected function beforeTearDown(): void
    {
        $path       = $this->getOverridePath();
        $fileSystem = new Filesystem();
        if ($fileSystem->exists($path)) {
            $fileSystem->remove($path);
        }
    }

    private function getOverridePath(): string
    {
        $pathsHelper = $this->getService(PathsHelper::class);

        return $pathsHelper->getThemesPath().'/system/UserBundle/Resources/views/Profile';
    }

    private function createOverrideFile(): void
    {
        $fs      = new Filesystem();
        $content = "{% extends '@MauticCore/Default/content.html.twig' %} {% block headerTitle %}Override test{% endblock %} {% block content %}Override test{% endblock %}";

        $fs->dumpFile($this->getOverridePath().'/index.html.twig', $content);
    }
}
