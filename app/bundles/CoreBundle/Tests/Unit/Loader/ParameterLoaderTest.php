<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Loader;

use Mautic\CoreBundle\Loader\ParameterLoader;
use PHPUnit\Framework\TestCase;

final class ParameterLoaderTest extends TestCase
{
    public function testParametersAreLoaded(): void
    {
        $envParameters = json_encode(['default_daterange_filter' => '-1 day']);
        putenv('MAUTIC_CONFIG_PARAMETERS='.$envParameters);

        $loader = new ParameterLoader(__DIR__.'/TestRoot/app');
        $loader->loadIntoEnvironment();

        $parameterBag = $loader->getParameterBag();

        $this->assertEquals('https://language-packs.mautic.com/', $parameterBag->get('translations_fetch_url'));
        $this->assertEquals('https://language-packs.mautic.com/', $_ENV['MAUTIC_TRANSLATIONS_FETCH_URL']);

        $this->assertEquals('-1 day', $parameterBag->get('default_daterange_filter'));
        $this->assertEquals('-1 day', $_ENV['MAUTIC_DEFAULT_DATERANGE_FILTER']);

        putenv('MAUTIC_CONFIG_PARAMETERS=');
    }

    public function testDefaultParametersAreLoaded(): void
    {
        $loader = new ParameterLoader(__DIR__.'/TestRoot/app');
        $this->assertIsArray($loader->getDefaultParameters());
        $this->assertFalse($loader->getDefaultParameters()['api_enabled']);
    }

    public function testGetWebrootDirReturnsProjectRootWhenNoWebRootConfigured(): void
    {
        $tempDir = sys_get_temp_dir().'/mautic_test_'.uniqid();
        mkdir($tempDir);

        // Create a composer.json without web-root configuration
        file_put_contents($tempDir.'/composer.json', json_encode([
            'name' => 'test/project',
            'extra' => [
                'public-dir' => '.',
            ],
        ]));

        $result = ParameterLoader::getWebrootDir($tempDir);

        $this->assertEquals($tempDir, $result);

        // Cleanup
        unlink($tempDir.'/composer.json');
        rmdir($tempDir);
    }

    public function testGetWebrootDirDetectsMauticScaffoldWebRoot(): void
    {
        $tempDir = sys_get_temp_dir().'/mautic_test_'.uniqid();
        mkdir($tempDir);
        mkdir($tempDir.'/docroot');

        // Create a composer.json with mautic-scaffold web-root (recommended-project style)
        file_put_contents($tempDir.'/composer.json', json_encode([
            'name' => 'mautic/recommended-project',
            'extra' => [
                'mautic-scaffold' => [
                    'locations' => [
                        'web-root' => 'docroot/',
                    ],
                ],
            ],
        ]));

        $result = ParameterLoader::getWebrootDir($tempDir);

        $this->assertEquals($tempDir.'/docroot', $result);

        // Cleanup
        unlink($tempDir.'/composer.json');
        rmdir($tempDir.'/docroot');
        rmdir($tempDir);
    }

    public function testGetWebrootDirDetectsSymfonyPublicDir(): void
    {
        $tempDir = sys_get_temp_dir().'/mautic_test_'.uniqid();
        mkdir($tempDir);
        mkdir($tempDir.'/public');

        // Create a composer.json with Symfony's public-dir
        file_put_contents($tempDir.'/composer.json', json_encode([
            'name' => 'test/project',
            'extra' => [
                'public-dir' => 'public',
            ],
        ]));

        $result = ParameterLoader::getWebrootDir($tempDir);

        $this->assertEquals($tempDir.'/public', $result);

        // Cleanup
        unlink($tempDir.'/composer.json');
        rmdir($tempDir.'/public');
        rmdir($tempDir);
    }

    public function testGetWebrootDirFallsBackToProjectRootWhenDirectoryDoesNotExist(): void
    {
        $tempDir = sys_get_temp_dir().'/mautic_test_'.uniqid();
        mkdir($tempDir);

        // Create a composer.json pointing to a non-existent directory
        file_put_contents($tempDir.'/composer.json', json_encode([
            'name' => 'test/project',
            'extra' => [
                'mautic-scaffold' => [
                    'locations' => [
                        'web-root' => 'nonexistent/',
                    ],
                ],
            ],
        ]));

        $result = ParameterLoader::getWebrootDir($tempDir);

        $this->assertEquals($tempDir, $result);

        // Cleanup
        unlink($tempDir.'/composer.json');
        rmdir($tempDir);
    }
}
