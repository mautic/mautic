<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20240725105507 extends PreUpAssertionMigration
{
    private const COUNTRY_TURKEY  = 'Turkey';
    private const COUNTRY_TURKIYE = 'Türkiye';

    protected function preUpAssertions(): void
    {
    }

    public function up(Schema $schema): void
    {
        $this->connection->update(
            $this->prefix.'leads',
            ['country' => self::COUNTRY_TURKIYE],
            ['country' => self::COUNTRY_TURKEY]
        );

        $this->connection->update(
            $this->prefix.'companies',
            ['companycountry' => self::COUNTRY_TURKIYE],
            ['companycountry' => self::COUNTRY_TURKEY]
        );
    }

    public function down(Schema $schema): void
    {
        $this->connection->update(
            $this->prefix.'leads',
            ['country' => self::COUNTRY_TURKEY],
            ['country' => self::COUNTRY_TURKIYE]
        );

        $this->connection->update(
            $this->prefix.'companies',
            ['companycountry' => self::COUNTRY_TURKEY],
            ['companycountry' => self::COUNTRY_TURKIYE]
        );
    }
}
