<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20240725105507 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
    }

    public function up(Schema $schema): void
    {
        $this->connection->update(
            $this->prefix . 'leads',
            ['country' => 'Türkiye'],
            ['country' => 'Turkey']
        );

        $this->connection->update(
            $this->prefix . 'companies',
            ['companycountry' => 'Türkiye'],
            ['companycountry' => 'Turkey']
        );
    }

    public function down(Schema $schema): void
    {
        $this->connection->update(
            $this->prefix . 'leads',
            ['country' => 'Turkey'],
            ['country' => 'Türkiye']
        );

        $this->connection->update(
            $this->prefix . 'companies',
            ['companycountry' => 'Turkey'],
            ['companycountry' => 'Türkiye']
        );
    }
}