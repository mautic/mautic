<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
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
