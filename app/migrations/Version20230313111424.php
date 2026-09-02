<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\SmsBundle\Entity\Sms;

final class Version20230313111424 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->getTable($this->getPrefixedTableName(Sms::TABLE_NAME))->hasColumn('is_mms');
        }, 'Column is_mms already exists');
    }

    public function up(Schema $schema): void
    {
        $schema->getTable($this->getPrefixedTableName(Sms::TABLE_NAME))->addColumn('is_mms', Types::BOOLEAN, ['default' => false, 'notnull' => true]);
    }
}
