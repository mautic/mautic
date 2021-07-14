<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\Loader\EnvVars;

use Mautic\CoreBundle\Loader\EnvVars\ConfigEnvVars;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

class ConfigEnvVarsTest extends TestCase
{
    /**
     * @var ParameterBag
     */
    protected $config;

    /**
     * @var ParameterBag
     */
    protected $defaultConfig;

    /**
     * @var ParameterBag
     */
    protected $envVars;

    protected function setUp(): void
    {
        $this->config        = new ParameterBag();
        $this->defaultConfig = new ParameterBag();
        $this->envVars       = new ParameterBag();
    }

    public function testGetEnvWorks()
    {
        putenv('MAUTIC_FOOBAR=bar');
        $this->config->set('foo', 'getenv(MAUTIC_FOOBAR)');

        ConfigEnvVars::load($this->config, $this->defaultConfig, $this->envVars);

        $this->assertEquals('bar', $this->envVars->get('MAUTIC_FOO'));
    }

    public function testLocalValueIsSet()
    {
        $this->config->set('foo', 'bar');

        ConfigEnvVars::load($this->config, $this->defaultConfig, $this->envVars);

        $this->assertEquals('bar', $this->envVars->get('MAUTIC_FOO'));
    }

    public function testValueIsJsonEncodedIfArray()
    {
        $this->config->set('foo', ['bar']);

        ConfigEnvVars::load($this->config, $this->defaultConfig, $this->envVars);

        $this->assertEquals('["bar"]', $this->envVars->get('MAUTIC_FOO'));
    }

    public function testDefaultValueIsJsonEncodedIfArray()
    {
        $this->config->set('foo', null);
        $this->defaultConfig->set('foo', ['bar']);

        ConfigEnvVars::load($this->config, $this->defaultConfig, $this->envVars);

        $this->assertEquals('["bar"]', $this->envVars->get('MAUTIC_FOO'));
    }
}
