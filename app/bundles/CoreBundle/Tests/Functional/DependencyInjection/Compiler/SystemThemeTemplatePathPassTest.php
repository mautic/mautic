<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\DependencyInjection\Compiler;

use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

class SystemThemeTemplatePathPassTest extends MauticMysqlTestCase
{
    public function testUserProfilePageOverrideFromSystemThemDirectory(): void
    {
        if (!file_exists($this->getOverridePath())) {
            $this->markTestSkipped(sprintf('The `%s` file is missing. Please copy the file from the following location: `%s`.',
                'themes/system/CoreBundle/Resources/views/Override/index.twig.html',
                '.github/ci-files/CoreBundle-Override-index.html.twig'
            ));
        }

        $this->client->request(Request::METHOD_GET, '/s/override-path');

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

        return $pathsHelper->getThemesPath().'/system/CoreBundle/Resources/views/Override';
    }
}
