<?php

namespace Mautic\PluginBundle\Model;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Helper\BundleHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Field\FieldList;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\Plugin;
use Mautic\PluginBundle\Entity\PluginRepository;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @extends FormModel<Plugin>
 */
class PluginModel extends FormModel
{
    public static function getName(): string
    {
        return 'plugin.plugin';
    }

    private FieldList $fieldList;

    private BundleHelper $bundleHelper;

    private PluginRepository $pluginRepository;

    #[Required]
    public function autowirePluginModel(
        FieldList $fieldList,
        BundleHelper $bundleHelper,
        PluginRepository $pluginRepository,
    ): void {
        $this->fieldList        = $fieldList;
        $this->bundleHelper     = $bundleHelper;
        $this->pluginRepository = $pluginRepository;
    }

    public function getRepository(): PluginRepository
    {
        return $this->pluginRepository;
    }

    public function getPermissionBase(): string
    {
        return 'plugin:plugins';
    }

    /**
     * Get lead fields used in selects/matching.
     *
     * @return mixed[]
     */
    public function getLeadFields(): array
    {
        return $this->fieldList->getFieldList();
    }

    /**
     * @return mixed[]
     */
    public function getCompanyFields(): array
    {
        return $this->fieldList->getFieldList(true, true, ['isPublished' => true, 'object' => 'company']);
    }

    public function saveFeatureSettings(Integration $entity): void
    {
        $this->em->persist($entity);
        $this->em->flush();
    }

    /**
     * Loads config.php arrays for all plugins.
     */
    public function getAllPluginsConfig(): array
    {
        return $this->bundleHelper->getPluginBundles();
    }

    /**
     * Loads all installed Plugin entities from database.
     *
     * @return Plugin[]
     */
    public function getInstalledPlugins()
    {
        return $this->getEntities(
            [
                'index' => 'bundle',
            ]
        );
    }

    /**
     * Returns metadata for all plugins.
     *
     * @return array<string, array<class-string, ClassMetadata>>
     */
    public function getPluginsMetadata(): array
    {
        $allMetadata     = $this->em->getMetadataFactory()->getAllMetadata();
        $pluginsMetadata = [];

        foreach ($allMetadata as $meta) {
            $namespace = $meta->namespace;

            if (str_contains($namespace, 'MauticPlugin')) {
                $bundleName = preg_replace('/\\\Entity$/', '', $namespace);
                $pluginsMetadata[$bundleName] ??= [];
                $pluginsMetadata[$bundleName][$meta->getName()] = $meta;
            }
        }

        return $pluginsMetadata;
    }

    /**
     * Returns all tables of installed plugins.
     *
     * @param array<string, array<class-string, ClassMetadata>> $pluginsMetadata
     *
     * @return array<string, array<int, Table>>
     */
    public function getInstalledPluginTables(array $pluginsMetadata): array
    {
        $currentSchema          = $this->em->getConnection()->createSchemaManager()->introspectSchema();
        $installedPluginsTables = [];

        foreach ($pluginsMetadata as $bundleName => $pluginMetadata) {
            foreach ($pluginMetadata as $meta) {
                $table = $meta->getTableName();

                $installedPluginsTables[$bundleName] ??= [];

                if ($currentSchema->hasTable($table)) {
                    $installedPluginsTables[$bundleName][] = $currentSchema->getTable($table);
                }
            }
        }

        return $installedPluginsTables;
    }

    /**
     * Generates new Schema objects for all installed plugins.
     */
    public function createPluginSchemas(array $installedPluginsTables): array
    {
        $installedPluginsSchemas = [];
        foreach ($installedPluginsTables as $bundleName => $tables) {
            $installedPluginsSchemas[$bundleName] = new Schema($tables);
        }

        return $installedPluginsSchemas;
    }
}
