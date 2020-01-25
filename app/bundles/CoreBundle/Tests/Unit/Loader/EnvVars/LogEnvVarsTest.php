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

use Mautic\CoreBundle\Loader\EnvVars\LogEnvVars;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

class LogEnvVarsTest extends TestCase
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

    protected function setUp()
    {
        $this->config        = new ParameterBag();
        $this->defaultConfig = new ParameterBag();
        $this->envVars       = new ParameterBag();
    }

    public function testDebugModeEnabledSetsCorrectLevels()
    {
        $this->config->set('debug', true);
        LogEnvVars::load($this->config, $this->defaultConfig, $this->envVars);

        $this->assertEquals('mautic.monolog.fulltrace.formatter', $this->envVars->get('MAUTIC_LOG_MAIN_FORMATTER'));
        $this->assertEquals('debug', $this->envVars->get('MAUTIC_LOG_MAIN_ACTION_LEVEL'));
        $this->assertEquals('debug', $this->envVars->get('MAUTIC_LOG_NESTED_ACTION_LEVEL'));
        $this->assertEquals('mautic.monolog.fulltrace.formatter', $this->envVars->get('MAUTIC_LOG_MAUTIC_FORMATTER'));
        $this->assertEquals('debug', $this->envVars->get('MAUTIC_LOG_MAUTIC_ACTION_LEVEL'));
    }

    public function testDebugModeDisabledSetsCorrectLevels()
    {
        $this->config->set('debug', false);
        LogEnvVars::load($this->config, $this->defaultConfig, $this->envVars);

        $this->assertEquals(null, $this->envVars->get('MAUTIC_LOG_MAIN_FORMATTER'));
        $this->assertEquals('error', $this->envVars->get('MAUTIC_LOG_MAIN_ACTION_LEVEL'));
        $this->assertEquals('error', $this->envVars->get('MAUTIC_LOG_NESTED_ACTION_LEVEL'));
        $this->assertEquals(null, $this->envVars->get('MAUTIC_LOG_MAUTIC_FORMATTER'));
        $this->assertEquals('notice', $this->envVars->get('MAUTIC_LOG_MAUTIC_ACTION_LEVEL'));
    }
}
