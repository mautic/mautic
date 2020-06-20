<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Doctrine\Provider;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumns;
use Mautic\CoreBundle\Event\GeneratedColumnsEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class GeneratedColumnsProvider implements GeneratedColumnsProviderInterface
{
    /**
     * @var string
     *
     * @see https://dev.mysql.com/doc/refman/5.7/en/innodb-foreign-key-constraints.html#innodb-foreign-key-generated-columns
     */
    const MYSQL_MINIMUM_VERSION = '5.7.14';

    /**
     * @var string
     *
     * @see https://mariadb.com/kb/en/library/generated-columns
     */
    const MARIADB_MINIMUM_VERSION = '5.2';

    /**
     * @var VersionProviderInterface
     */
    private $versionProvider;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var GeneratedColumns
     */
    private $generatedColumns;

    public function __construct(
        VersionProviderInterface $versionProvider,
        EventDispatcherInterface $dispatcher
    ) {
        $this->versionProvider = $versionProvider;
        $this->dispatcher      = $dispatcher;
    }

    public function getGeneratedColumns(): GeneratedColumns
    {
        if (null !== $this->generatedColumns) {
            return $this->generatedColumns;
        }

        if ($this->generatedColumnsAreSupported()) {
            $event = new GeneratedColumnsEvent();
            $this->dispatcher->dispatch(CoreEvents::ON_GENERATED_COLUMNS_BUILD, $event);
            $this->generatedColumns = $event->getGeneratedColumns();
        } else {
            $this->generatedColumns = new GeneratedColumns();
        }

        return $this->generatedColumns;
    }

    public function generatedColumnsAreSupported(): bool
    {
        return 1 !== version_compare($this->getMinimalSupportedVersion(), $this->versionProvider->getVersion());
    }

    public function getMinimalSupportedVersion(): string
    {
        if ($this->versionProvider->isMariaDb()) {
            return self::MARIADB_MINIMUM_VERSION;
        }

        return self::MYSQL_MINIMUM_VERSION;
    }
}
