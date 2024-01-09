<?php

namespace Mautic\PluginBundle\Model;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\BundleHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\PluginBundle\Entity\Plugin;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @extends FormModel<Plugin>
 */
class PluginModel extends FormModel
{
    public function __construct(
        protected FieldModel $leadFieldModel,
        CoreParametersHelper $coreParametersHelper,
        private BundleHelper $bundleHelper,
        EntityManager $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    /**
     * @return \Mautic\PluginBundle\Entity\PluginRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(\Mautic\PluginBundle\Entity\Plugin::class);
    }

    public function getIntegrationEntityRepository()
    {
        return $this->em->getRepository(\Mautic\PluginBundle\Entity\IntegrationEntity::class);
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
        return $this->leadFieldModel->getFieldList();
    }

    /**
     * Get Company fields.
     *
     * @return mixed[]
     */
    public function getCompanyFields(): array
    {
        return $this->leadFieldModel->getFieldList(true, true, ['isPublished' => true, 'object' => 'company']);
    }

    public function saveFeatureSettings($entity): void
    {
        $this->em->persist($entity);
        $this->em->flush();
    }

    /**
     * Loads config.php arrays for all plugins.
     *
     * @return array
     */
    public function getAllPluginsConfig()
    {
        return $this->bundleHelper->getPluginBundles();
    }

    /**
     * Loads all installed Plugin entities from database.
     *
     * @return array
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
     */
    public function getPluginsMetadata(): array
    {
        $allMetadata     = $this->em->getMetadataFactory()->getAllMetadata();
        $pluginsMetadata = [];

        foreach ($allMetadata as $meta) {
            $namespace = $meta->namespace;

            if (str_contains($namespace, 'MauticPlugin')) {
                $bundleName = preg_replace('/\\\Entity$/', '', $namespace);
                if (!isset($pluginsMetadata[$bundleName])) {
                    $pluginsMetadata[$bundleName] = [];
                }
                $pluginsMetadata[$bundleName][$meta->getName()] = $meta;
            }
        }

        return $pluginsMetadata;
    }

    /**
     * Returns all tables of installed plugins.
     */
    public function getInstalledPluginTables(array $pluginsMetadata): array
    {
        $currentSchema          = $this->em->getConnection()->getSchemaManager()->createSchema();
        $installedPluginsTables = [];

        foreach ($pluginsMetadata as $bundleName => $pluginMetadata) {
            foreach ($pluginMetadata as $meta) {
                $table = $meta->getTableName();

                if (!isset($installedPluginsTables[$bundleName])) {
                    $installedPluginsTables[$bundleName] = [];
                }

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
