<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Doctrine\Provider;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Helper\CoreParametersHelper;

final class VersionProvider implements VersionProviderInterface
{
    /**
     * @var string
     *
     * @see app/bundles/CoreBundle/Config/config.php and look for 'db_server_version'.
     */
    const DEFAULT_CONFIG_VERSION = '5.5';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var string
     */
    private $version;

    /**
     * @param Connection           $connection
     * @param CoreParametersHelper $coreParametersHelper
     */
    public function __construct(Connection $connection, CoreParametersHelper $coreParametersHelper)
    {
        $this->connection           = $connection;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        if (null === $this->version) {
            try {
                $this->version = $this->getVersionFromConfig();
            } catch (\UnexpectedValueException $e) {
                $this->version = $this->fetchVersionFromDb();
            }
        }

        return $this->version;
    }

    /**
     * @return bool
     */
    public function isMariaDb()
    {
        return strpos($this->getVersion(), 'MariaDB') !== false;
    }

    /**
     * @return bool
     */
    public function isMySql()
    {
        return !$this->isMariaDb();
    }

    /**
     * @return string
     *
     * @throws \UnexpectedValueException
     */
    private function getVersionFromConfig()
    {
        $version = $this->coreParametersHelper->getParameter('db_server_version');

        if (empty($version)) {
            throw new \UnexpectedValueException('db_server_version has empty value. Set it in app/config/local.php.');
        }

        if (self::DEFAULT_CONFIG_VERSION === $version) {
            throw new \UnexpectedValueException('db_server_version has default value of '.self::DEFAULT_CONFIG_VERSION.'. That is suspicious and the version is not probably set in app/config/local.php.');
        }

        return $version;
    }

    /**
     * @return string
     */
    private function fetchVersionFromDb()
    {
        return $this->connection->executeQuery('SELECT VERSION()')->fetchColumn();
    }
}
