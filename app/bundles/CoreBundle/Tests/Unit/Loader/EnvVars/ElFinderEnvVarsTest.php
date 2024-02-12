<?php

namespace Mautic\CoreBundle\Tests\Unit\Loader\EnvVars;

use Mautic\CoreBundle\Loader\EnvVars\ElFinderEnvVars;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

class ElFinderEnvVarsTest extends TestCase
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

    public function testPathAndUrlSet(): void
    {
        $this->config->set('image_path', 'images');
        $this->config->set('site_url', 'https://foo.bar/test');

        ElFinderEnvVars::load($this->config, $this->defaultConfig, $this->envVars);
        $this->assertStringEndsWith('images', $this->envVars->get('MAUTIC_EL_FINDER_PATH'));
        $this->assertEquals('https://foo.bar/test/images', $this->envVars->get('MAUTIC_EL_FINDER_URL'));
    }

    public function testTrailingSlashHandled(): void
    {
        $this->config->set('image_path', 'images/');
        $this->config->set('site_url', 'https://foo.bar/test/');

        ElFinderEnvVars::load($this->config, $this->defaultConfig, $this->envVars);
        $this->assertStringEndsWith('images', $this->envVars->get('MAUTIC_EL_FINDER_PATH'));
        $this->assertEquals('https://foo.bar/test/images', $this->envVars->get('MAUTIC_EL_FINDER_URL'));
    }

    public function testThatTheLocalRootHasPriorityOverTheKernelRootDir(): void
    {
        $this->defaultConfig->set('local_root', '/foo/bar');
        $this->config->set('image_path', 'images/');

        ElFinderEnvVars::load($this->config, $this->defaultConfig, $this->envVars);
        $this->assertSame('/foo/bar/images', $this->envVars->get('MAUTIC_EL_FINDER_PATH'));
    }
}
