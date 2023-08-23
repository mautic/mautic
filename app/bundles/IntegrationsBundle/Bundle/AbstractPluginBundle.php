<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Bundle;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\IntegrationsBundle\Migration\Engine;
use Mautic\PluginBundle\Bundle\PluginBundleBase;
use Mautic\PluginBundle\Entity\Plugin;

/**
 * Base Bundle class which should be extended by addon bundles.
 */
abstract class AbstractPluginBundle extends PluginBundleBase
{
    /**
     * @param array|null $metadata
     *
     * @throws \Exception
     */
    public static function onPluginUpdate(Plugin $plugin, MauticFactory $factory, $metadata = null, ?Schema $installedSchema = null): void
    {
        $entityManager = $factory->getEntityManager();
        $tablePrefix   = (string) $factory->getParameter('mautic.db_table_prefix');

        $migrationEngine = new Engine(
            $entityManager,
            $tablePrefix,
            __DIR__.'/../../../../plugins/'.$plugin->getBundle(),
            $plugin->getBundle()
        );

        $migrationEngine->up();
    }
}
