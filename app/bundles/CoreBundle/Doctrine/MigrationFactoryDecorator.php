<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\LeadBundle\Field\Helper\IndexHelper;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This is the only way how to inject dependencies to migrations in Symfony 7.
 *
 * @see https://github.com/doctrine/DoctrineMigrationsBundle/issues/521
 */
#[AsDecorator('doctrine.migrations.migrations_factory')]
final readonly class MigrationFactoryDecorator implements MigrationFactory
{
    public function __construct(
        private MigrationFactory $migrationFactory,
        #[AutowireLocator([
            CoreParametersHelper::class,
            IndexHelper::class, // Version20190524124819
            'doctrine.orm.entity_manager', // Version20201026101117
            ModelFactory::class, // Version20211209022550
            IntegrationHelper::class, // Version20221128145933
            Configurator::class, // Version20230615101328, Version20230627140512
            PathsHelper::class, // Versionzz20230929183000
            // Add more services if you need them in a migration.
        ])]
        private ContainerInterface $locator,
    ) {
    }

    public function createVersion(string $migrationClassName): AbstractMigration
    {
        $instance = $this->migrationFactory->createVersion($migrationClassName);

        if (is_subclass_of($instance, AbstractMauticMigration::class)) {
            $instance->setContainer($this->locator);
        }

        return $instance;
    }
}
