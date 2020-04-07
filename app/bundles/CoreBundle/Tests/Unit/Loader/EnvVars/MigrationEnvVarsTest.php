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

use Mautic\CoreBundle\Loader\EnvVars\MigrationsEnvVars;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

class MigrationEnvVarsTest extends TestCase
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

    public function testTablePrefixIsSetOnMigrations()
    {
        $this->config->set('db_table_prefix', 'foobar_');
        MigrationsEnvVars::load($this->config, $this->defaultConfig, $this->envVars);
        $this->assertEquals('foobar_migrations', $this->envVars->get('MAUTIC_MIGRATIONS_TABLE_NAME'));
    }

    public function testTablePrefixEmptyJustIncludesDefaultTableName()
    {
        $this->config->set('db_table_prefix', '');
        MigrationsEnvVars::load($this->config, $this->defaultConfig, $this->envVars);
        $this->assertEquals('migrations', $this->envVars->get('MAUTIC_MIGRATIONS_TABLE_NAME'));
    }
}
