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

use Mautic\CoreBundle\Loader\EnvVars\TwigEnvVars;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

class TwigEnvVarsTest extends TestCase
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

    public function testTwigCachDirectoryToTmpPath()
    {
        $this->config->set('tmp_path', '/foo/bar');
        TwigEnvVars::load($this->config, $this->defaultConfig, $this->envVars);

        $this->assertEquals('/foo/bar/twig', $this->envVars->get('MAUTIC_TWIG_CACHE_DIR'));
    }
}
