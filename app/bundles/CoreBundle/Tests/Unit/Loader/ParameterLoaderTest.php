<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Loader;

use Mautic\CoreBundle\Loader\ParameterLoader;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
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

        $this->assertSame($tempDir, $result);

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

        $this->assertSame($tempDir.'/docroot', $result);

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

        $this->assertSame($tempDir.'/public', $result);

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

        $this->assertSame($tempDir, $result);

        // Cleanup
        unlink($tempDir.'/composer.json');
        rmdir($tempDir);
    }

    /**
     * A value already present because a real .env/.env.local file set it earlier during the
     * application's bootstrap must win over the value computed from local.php. Symfony tracks
     * such values in $_ENV['SYMFONY_DOTENV_VARS'] so that later .env layers (.env.local,
     * .env.$env, ...) may still override them - and Dotenv::populate() only protects values that
     * are *not* tracked there. That is exactly how this bug slipped through: our own later
     * populate() call was indistinguishable from just another legitimate .env cascade layer, so
     * it was allowed to silently overwrite a real .env file's value, even though a plain system
     * environment variable of the same name (never tracked in SYMFONY_DOTENV_VARS) would not be.
     *
     * Run in an isolated process: the fix only protects the first time ParameterLoader populates
     * a given key in a process (see ParameterLoader::$selfPopulatedEnvKeys), so this needs a
     * process where MAUTIC_MAILER_DSN has not already been touched by another test's loader call.
     */
    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testLoadIntoEnvironmentDoesNotOverrideAnExistingEnvValue(): void
    {
        // The TestRoot fixture's local.php sets 'mailer_dsn' => 'foobar.com', which is mapped to
        // the MAUTIC_MAILER_DSN environment variable.
        $_ENV['MAUTIC_MAILER_DSN']   = 'from-dotenv-file';
        $_ENV['SYMFONY_DOTENV_VARS'] = 'MAUTIC_MAILER_DSN';

        $loader = new ParameterLoader(__DIR__.'/TestRoot/app');
        $loader->loadIntoEnvironment();

        $this->assertEquals('from-dotenv-file', $_ENV['MAUTIC_MAILER_DSN']);
    }
}
