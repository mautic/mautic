<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\Configurator\Step;

use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Configurator\Step\StepInterface;
use Mautic\InstallBundle\Configurator\Form\DoctrineStepType;

/**
 * Doctrine Step.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class DoctrineStep implements StepInterface
{
    /**
     * Database driver.
     */
    public $driver = 'pdo_mysql';

    /**
     * Database host.
     */
    public $host = 'localhost';

    /**
     * Database table prefix.
     *
     * @var string
     */
    public $table_prefix;

    /**
     * Database connection port.
     */
    public $port = 3306;

    /**
     * Database name.
     */
    public $name;

    /**
     * Database user.
     */
    public $user;

    /**
     * Database user's password.
     *
     * @var string
     */
    public $password;

    /**
     * Backup tables if they exist; otherwise drop them.
     *
     * @var bool
     */
    public $backup_tables = true;

    /**
     * Prefix for backup tables.
     *
     * @var string
     */
    public $backup_prefix = 'bak_';

    /**
     * @var
     */
    public $server_version = '5.5';

    /**
     * Constructor.
     *
     * @param Configurator $configurator
     */
    public function __construct(Configurator $configurator)
    {
        $parameters = $configurator->getParameters();

        foreach ($parameters as $key => $value) {
            if (0 === strpos($key, 'db_')) {
                $parameters[substr($key, 3)] = $value;
                $key                         = substr($key, 3);
                $this->$key                  = $value;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFormType()
    {
        return new DoctrineStepType();
    }

    /**
     * {@inheritdoc}
     */
    public function checkRequirements()
    {
        $messages = [];

        if (!class_exists('\PDO')) {
            $messages[] = 'mautic.install.pdo.mandatory';
        } else {
            $drivers = \PDO::getAvailableDrivers();
            if (0 == count($drivers)) {
                $messages[] = 'mautic.install.pdo.drivers';
            }
        }

        return $messages;
    }

    /**
     * {@inheritdoc}
     */
    public function checkOptionalSettings()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function update(StepInterface $data)
    {
        $parameters = [];

        foreach ($data as $key => $value) {
            $parameters['db_'.$key] = $value;
        }

        return $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'MauticInstallBundle:Install:doctrine.html.php';
    }

    /**
     * Return the key values of the available driver array.
     *
     * @return array
     */
    public static function getDriverKeys()
    {
        return array_keys(static::getDrivers());
    }

    /**
     * Fetches the available database drivers for the environment.
     *
     * @return array
     */
    public static function getDrivers()
    {
        $mauticSupported = [
            'pdo_mysql' => 'MySQL PDO (Recommended)',
            'mysqli'    => 'MySQLi',
            //'pdo_pgsql' => 'PostgreSQL',
            //'pdo_sqlite' => 'SQLite',
            //'pdo_sqlsrv' => 'SQL Server',
            //'pdo_oci'    => 'Oracle (PDO)',
            //'pdo_ibm'    => 'IBM DB2 (PDO)',
            //'oci8'       => 'Oracle (native)',
            //'ibm_db2'    => 'IBM DB2 (native)',
        ];

        $supported = [];

        // Add PDO drivers if supported
        if (class_exists('\PDO')) {
            $pdoDrivers = \PDO::getAvailableDrivers();

            foreach ($pdoDrivers as $driver) {
                if (array_key_exists('pdo_'.$driver, $mauticSupported)) {
                    $supported['pdo_'.$driver] = $mauticSupported['pdo_'.$driver];
                }
            }
        }

        // Add MySQLi if available
        if (function_exists('mysqli_connect')) {
            $supported['mysqli'] = $mauticSupported['mysqli'];
        }

        return $supported;
    }
}
