<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\CoreBundle\Helper\Dsn\Dsn;

final class Version20230615101328 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $configurator = $this->getConfigurator();

        $this->skipAssertion(
            fn () => !$configurator->isFileWritable(),
            'The local.php file is not writable. Skipping the email configuration migration.'
        );

        $this->skipAssertion(
            fn () => array_key_exists('mailer_dsn', $configurator->getParameters()),
            'The mailer_dsn parameter is already set. Skipping the email configuration migration.'
        );
    }

    public function up(Schema $schema): void
    {
        $configurator = $this->getConfigurator();
        $parameters   = $configurator->getParameters();
        $dsn          = new Dsn(
            $parameters['mailer_transport'] ?? 'smtp',
            $parameters['mailer_host'] ?? 'localhost',
            $parameters['mailer_user'] ?? null,
            $parameters['mailer_password'] ?? null,
            (int) ($parameters['mailer_port'] ?? 25),
        );

        $parameters['mailer_dsn'] = str_replace('%', '%%', (string) $dsn);

        $configurator->mergeParameters($parameters);
        $configurator->write();
    }

    private function getConfigurator(): Configurator
    {
        return $this->container->get(Configurator::class);
    }
}
