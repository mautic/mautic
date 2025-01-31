<?php

namespace Mautic\CoreBundle\Tests\Unit\Loader;

use Mautic\CoreBundle\Loader\ParameterLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Dotenv\Dotenv;

class ParameterLoaderTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    public array $env;

    /**
     * @var array<string, mixed>
     */
    public array $server;

    public function setUp(): void
    {
        parent::setUp();

        $this->env    = $_ENV;
        $this->server = $_SERVER;
    }

    public function tearDown(): void
    {
        $_ENV    = $this->env;
        $_SERVER = $this->server;

        parent::tearDown();
    }

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

    public function testEnvironmentVariablesOverruleParameters(): void
    {
        $_ENV['MAUTIC_MAILER_DSN'] = 'mailer-dsn-system-environment';

        $loader = new ParameterLoader(__DIR__.'/TestRoot/app');
        $loader->loadIntoEnvironment();

        $this->assertEquals('foobar.com', $loader->getParameterBag()->get('mailer_dsn'));
        $this->assertEquals('mailer-dsn-system-environment', $_ENV['MAUTIC_MAILER_DSN']);
    }

    public function testDotEnvVariablesOverruleParameters(): void
    {
        $dotenv = new Dotenv();
        $dotenv->load(__DIR__.'/TestRoot/.env');

        $loader = new ParameterLoader(__DIR__.'/TestRoot/app');
        $loader->loadIntoEnvironment();

        $this->assertEquals('foobar.com', $loader->getParameterBag()->get('mailer_dsn'));
        $this->assertEquals('mailer-dsn-dotenv', $_ENV['MAUTIC_MAILER_DSN']);
    }
}
