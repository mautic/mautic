<?php

namespace Mautic\CoreBundle\Tests\Unit\Loader\EnvVars;

use Mautic\CoreBundle\Loader\EnvVars\SessionEnvVars;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

class SessionEnvVarsTest extends TestCase
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

    public function testSessionNameIsCorrectlyGenerated(): void
    {
        $this->config->set('secret_key', 'topsecret');
        $this->defaultConfig->set('local_config_path', '/foo/bar');
        $sessionName = md5(md5('/foo/bar').'topsecret');

        SessionEnvVars::load($this->config, $this->defaultConfig, $this->envVars);

        $this->assertEquals($sessionName, $this->envVars->get('MAUTIC_SESSION_NAME'));
    }
}
