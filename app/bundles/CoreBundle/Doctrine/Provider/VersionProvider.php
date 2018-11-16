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
        if ($this->coreParametersHelper->hasParameter('db_server_version')) {
            return $this->coreParametersHelper->getParameter('db_server_version');
        }

        throw new \UnexpectedValueException('db_server_version is not set in the config file.');
    }

    /**
     * @return string
     */
    private function fetchVersionFromDb()
    {
        return $this->connection->executeQuery('SELECT VERSION()')->fetchColumn();
    }
}
