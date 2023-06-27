<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20230627140512 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $configurator = $this->getConfigurator();

        $this->skipAssertion(
            fn () => !$configurator->isFileWritable(),
            'The local.php file is not writable. Skipping the migration. Replace the usages of "%kernel.root_dir%" in your local.config file with "%kernel.project_dir%/app".'
        );

        $this->skipAssertion(
            fn () => str_contains('%kernel.root_dir%', $configurator->render()),
            'The deprecated %kernel.root_dir% is unused. Your local.php file is just fine. Skipping the migration.'
        );
    }

    public function up(Schema $schema): void
    {
        $configurator = $this->getConfigurator();

        $configurator->mergeParameters(
            array_map(
                function ($value) {
                    if (is_string($value) && str_contains($value, '%kernel.root_dir%/..')) {
                        return str_replace('%kernel.root_dir%/..', '%kernel.project_dir%', $value);
                    }
                    if (is_string($value) && str_contains($value, '%kernel.root_dir%')) {
                        return str_replace('%kernel.root_dir%', '%kernel.project_dir%/app', $value);
                    }

                    return $value;
                },
                $configurator->getParameters()
            )
        );

        $configurator->write();
    }

    private function getConfigurator(): Configurator
    {
        return $this->container->get(Configurator::class);
    }
}
