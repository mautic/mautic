<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\Connection;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;

trait ConnectionInitializerTrait
{
    /**
     * @throws Exception
     */
    public function initConnection(array $dbParams): void
    {
        $params        = $this->getParams();
        $driver        = $dbParams['driver'] ?? $params['driver'];
        $driverChanged = $driver !== $params['driver'];
        $this->close();

        $settings = [
            'driver'   => $driver,
            'host'     => $dbParams['host'],
            'dbname'   => $dbParams['name'],
            'user'     => $dbParams['user'],
            'password' => $dbParams['password'],
            'charset'  => $dbParams['charset'] ?? ('pdo_pgsql' === $driver ? 'UTF8' : 'utf8mb4'),
        ];
        $configure = static function (array $params, array $settings) use ($driverChanged, $dbParams): array {
            $params = array_replace($params, $settings);
            if (empty($dbParams['port'])) {
                unset($params['port']);
            } else {
                $params['port'] = (int) $dbParams['port'];
            }
            if ($driverChanged) {
                unset($params['platform'], $params['serverVersion'], $params['driverClass'], $params['url']);
                // PDO driver-specific attributes reuse the same numbers across MySQL and PostgreSQL.
                $params['driverOptions'] = array_filter($params['driverOptions'] ?? [], static fn (int $attribute): bool => $attribute < 1000, ARRAY_FILTER_USE_KEY);
            }

            return $params;
        };
        $params = $configure($params, $settings);
        if (isset($params['primary'])) {
            $params['primary'] = $configure($params['primary'], $settings);
        }
        if (isset($params['replica'])) {
            unset($settings['host']);
            foreach ($params['replica'] as $name => $replica) {
                $params['replica'][$name] = $configure($replica, $settings);
            }
        }

        // @phpstan-ignore property.deprecated (DBAL 3 requires preserving the existing EventManager when changing drivers.)
        $connection = DriverManager::getConnection($params, $this->_config, $this->_eventManager);
        if ($driverChanged) {
            // Reset DBAL's cached platform without connecting before the installer creates the database.
            $params['platform'] = $connection->getDriver()->getDatabasePlatform();
            // @phpstan-ignore property.deprecated (DBAL 3 caches the previous driver's schema manager here.)
            $this->_schemaManager = null;
        }
        // @phpstan-ignore property.deprecated (DBAL 3 requires preserving the existing EventManager when changing drivers.)
        $this->__construct($params, $connection->getDriver(), $this->_config, $this->_eventManager);
    }
}
