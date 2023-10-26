<?php

namespace Mautic\CoreBundle\Tests\Unit\Loader;

use Mautic\CoreBundle\Loader\ParameterLoader;
use PHPUnit\Framework\TestCase;

class ParameterLoaderTest extends TestCase
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
}
