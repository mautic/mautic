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
    protected static array $backupDotEnv;

    /**
     * @var array<string, mixed>
     */
    protected static array $backupSysEnv;

    /**
     * @var array<int, string>
     */
    protected static array $backupEnvVars;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$backupEnvVars = [
            'MAUTIC_CONFIG_PARAMETERS',
            'MAUTIC_TRANSLATIONS_FETCH_URL',
            'MAUTIC_ALLOWED_EXTENSIONS',
        ];
        self::$backupSysEnv = array_intersect_key(getenv(), array_flip(self::$backupEnvVars));
        self::$backupDotEnv = array_intersect_key($_ENV, array_flip(self::$backupEnvVars));
    }

    public static function tearDownAfterClass(): void
    {
        array_walk(self::$backupSysEnv, function ($value, $key) { putenv("{$key}={$value}"); });
        array_walk(self::$backupDotEnv, function ($value, $key) { $_ENV[$key] = $value; });

        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::$backupEnvVars as $key) {
            putenv("{$key}=");
            unset($_ENV[$key]);
        }
    }

    public function testDefaultParametersAreLoaded(): void
    {
        $loader = new ParameterLoader(__DIR__.'/TestRoot/app');
        $this->assertEquals('smtp://localhost:25', $loader->getDefaultParameters()['mailer_dsn']);
        $this->assertEquals('https://language-packs.mautic.com/', $loader->getDefaultParameters()['translations_fetch_url']);
        $this->assertIsArray($loader->getDefaultParameters()['allowed_extensions']);
    }

    public function testParametersAreLoadedFromLocalConfig(): void
    {
        $loader = new ParameterLoader(__DIR__.'/TestRoot/app');
        $this->assertEquals('foobar.com', $loader->getParameterBag()->get('mailer_dsn'));
        $this->assertEquals('', $loader->getParameterBag()->get('translations_fetch_url'));
        $this->assertEquals('', $loader->getParameterBag()->get('allowed_extensions'));
    }

    public function testParametersAreLoadedFromDotEnvFile(): void
    {
        $dotEnv = new Dotenv();
        $dotEnv->loadEnv(__DIR__.'/TestRoot/.env');

        $loader = new ParameterLoader(__DIR__.'/TestRoot/app');
        $this->assertEquals('bar.com', $loader->getParameterBag()->get('mailer_dsn'));
        $this->assertEquals('https://language-packs.bar.com/', $loader->getParameterBag()->get('translations_fetch_url'));
        $this->assertEquals(['png'], $loader->getParameterBag()->get('allowed_extensions'));
    }

    public function testParametersAreLoadedFromSystemEnvironment(): void
    {
        $envParameters = json_encode(['mailer_dsn' => 'foo.com']);
        putenv('MAUTIC_CONFIG_PARAMETERS='.$envParameters);
        putenv('MAUTIC_TRANSLATIONS_FETCH_URL=https://language-packs.foo.com/');
        putenv('MAUTIC_ALLOWED_EXTENSIONS=["jpg"]');

        $loader = new ParameterLoader(__DIR__.'/TestRoot/app');
        $this->assertEquals('foo.com', $loader->getParameterBag()->get('mailer_dsn'));
        $this->assertEquals('https://language-packs.foo.com/', $loader->getParameterBag()->get('translations_fetch_url'));
        $this->assertEquals(['jpg'], $loader->getParameterBag()->get('allowed_extensions'));
    }
}
