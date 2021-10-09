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

use Mautic\CoreBundle\Loader\EnvVars\ApiEnvVars;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

class ApiEnvVarsTest extends TestCase
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

    public function testOauth2TokenLifetimesAreCalculatedWhenSet(): void
    {
        $this->config->set('api_oauth2_access_token_lifetime', 2);
        $this->config->set('api_oauth2_refresh_token_lifetime', 2);

        ApiEnvVars::load($this->config, $this->defaultConfig, $this->envVars);

        $this->assertEquals(120, $this->envVars->get('MAUTIC_API_OAUTH2_ACCESS_TOKEN_LIFETIME'));
        $this->assertEquals(172800, $this->envVars->get('MAUTIC_API_OAUTH2_REFRESH_TOKEN_LIFETIME'));
    }

    public function testOauth2TokenLifetimesAreDefaultWhenNotSet(): void
    {
        ApiEnvVars::load($this->config, $this->defaultConfig, $this->envVars);

        $this->assertEquals(3600, $this->envVars->get('MAUTIC_API_OAUTH2_ACCESS_TOKEN_LIFETIME'));
        $this->assertEquals(1209600, $this->envVars->get('MAUTIC_API_OAUTH2_REFRESH_TOKEN_LIFETIME'));
    }

    public function testRateLimitIsEnabled(): void
    {
        $this->config->set('api_rate_limiter_limit', 100);

        ApiEnvVars::load($this->config, $this->defaultConfig, $this->envVars);

        $this->assertTrue($this->envVars->get('MAUTIC_API_RATE_LIMIT_ENABLED'));
    }

    public function testRateLimitIsDisabled(): void
    {
        ApiEnvVars::load($this->config, $this->defaultConfig, $this->envVars);

        $this->assertFalse($this->envVars->get('MAUTIC_API_RATE_LIMIT_ENABLED'));
    }
}
