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

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumns;
use Mautic\CoreBundle\Event\GeneratedColumnsEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class GeneratedColumnsProvider implements GeneratedColumnsProviderInterface
{
    /**
     * @var string
     *
     * @see https://www.percona.com/blog/2015/04/29/generated-virtual-columns-in-mysql-5-7-labs
     */
    private const MYSQL_MINIMUM_VERSION = '5.7.7';

    /**
     * @var string
     *
     * @see https://mariadb.com/kb/en/library/generated-columns
     */
    private const MARIADB_MINIMUM_VERSION = '5.2';

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

    /**
     * @param VersionProviderInterface $versionProvider
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        VersionProviderInterface $versionProvider,
        EventDispatcherInterface $dispatcher
    ) {
        $this->versionProvider = $versionProvider;
        $this->dispatcher      = $dispatcher;
    }

    /**
     * @return GeneratedColumns
     */
    public function getGeneratedColumns()
    {
        if ($this->generatedColumnsAreSupported()
            && null === $this->generatedColumns
            && $this->dispatcher->hasListeners(CoreEvents::ON_GENERATED_COLUMNS_BUILD)
        ) {
            $event = new GeneratedColumnsEvent();
            $this->dispatcher->dispatch(CoreEvents::ON_GENERATED_COLUMNS_BUILD, $event);
            $this->generatedColumns = $event->getGeneratedColumns();
        } else {
            $this->generatedColumns = new GeneratedColumns();
        }

        return $this->generatedColumns;
    }

    /**
     * @return bool
     */
    public function generatedColumnsAreSupported()
    {
        return 1 !== version_compare($this->getMinimalSupportedVersion(), $this->versionProvider->fetchVersion());
    }

    /**
     * @return string
     */
    public function getMinimalSupportedVersion()
    {
        if ($this->versionProvider->isMariaDb()) {
            return self::MARIADB_MINIMUM_VERSION;
        }

        return self::MYSQL_MINIMUM_VERSION;
    }
}
