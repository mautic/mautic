<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\DependencyInjection\Compiler;

use Mautic\CoreBundle\Helper\CacheHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

class SystemThemeTemplatePathPassTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        // Create a twig file
        $this->createOverrideFile();

        // Clear the cache
        /** @var CacheHelper $cacheHelper */
        $cacheHelper = self::getContainer()->get('mautic.helper.cache');
        $cacheHelper->nukeCache();

        parent::setUp();
    }

    public function testUserProfilePageOverrideFromSystemThemDirectory(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/account');

        Assert::assertTrue($this->client->getResponse()->isOk());
        Assert::assertStringContainsString('Override test', $this->client->getResponse()->getContent(), 'Page has not override.');
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
        /** @var PathsHelper $pathsHelper */
        $pathsHelper  = static::getContainer()->get('mautic.helper.paths');

        return $pathsHelper->getThemesPath().'/system/UserBundle/Resources/views/Profile';
    }

    private function createOverrideFile(): void
    {
        $fs      = new Filesystem();
        $content = "{% extends '@MauticCore/Default/content.html.twig' %} {% block headerTitle %}Override test{% endblock %} {% block content %}Override test{% endblock %}";

        $fs->dumpFile($this->getOverridePath().'/index.html.twig', $content);
    }
}
