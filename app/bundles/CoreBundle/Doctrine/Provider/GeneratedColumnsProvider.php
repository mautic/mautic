<?php

declare(strict_types=1);

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
    public const MYSQL_MINIMUM_VERSION = '5.7.14';

    /**
     * @var string
     *
     * @see https://mariadb.com/kb/en/library/generated-columns
     */
    public const MARIADB_MINIMUM_VERSION = '10.2.6';

    /**
     * @var string
     *
     * Minimum Version (Stored): PostgreSQL 12.
     * Minimum Version (Virtual): PostgreSQL 18.
     *
     * @see https://www.postgresql.org/docs/current/ddl-generated-columns.html
     */
    public const POSTGRESQL_MINIMUM_VERSION = '18.0.0';

    private GeneratedColumns $generatedColumns;

    public function __construct(
        private readonly VersionProviderInterface $versionProvider,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
        $this->generatedColumns = new GeneratedColumns();
    }

    public function getGeneratedColumns(): GeneratedColumns
    {
        if ($this->generatedColumnsAreSupported()
            && 0 === $this->generatedColumns->count()
            && $this->dispatcher->hasListeners(CoreEvents::ON_GENERATED_COLUMNS_BUILD)
        ) {
            $event                  = $this->dispatcher->dispatch(new GeneratedColumnsEvent(), CoreEvents::ON_GENERATED_COLUMNS_BUILD);
            $this->generatedColumns = $event->getGeneratedColumns();
        }

        return $this->generatedColumns;
    }

    public function generatedColumnsAreSupported(): bool
    {
        if ($this->versionProvider->isPostgreSql()) {
            /*
             * We disable it on postgresql by default as the expressions we try to use are not immutable
             * ERROR:  generation expression is not immutable
             *
             * As workaround for postgresql in future we should probably use
             * standard column + trigger on update/edit which will fill the data.
             */
            return false;
        }
        $version = VersionProvider::getNumericVersion($this->versionProvider->getVersion());

        return 1 !== version_compare($this->getMinimalSupportedVersion(), $version);
    }

    public function getMinimalSupportedVersion(): string
    {
        if ($this->versionProvider->isPostgreSql()) {
            return self::POSTGRESQL_MINIMUM_VERSION;
        }

        if ($this->versionProvider->isMariaDb()) {
            return self::MARIADB_MINIMUM_VERSION;
        }

        return self::MYSQL_MINIMUM_VERSION;
    }
}
