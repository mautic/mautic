<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20241004132307 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'webhooks';

    public function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            $table = $schema->getTable($this->getPrefixedTableName());

            return $table->hasColumn('marked_unhealthy_at') && $table->hasColumn('unhealthy_since') && $table->hasColumn('last_notification_sent_at');
        }, sprintf('Columns already exists in %s', $this->getPrefixedTableName()));
    }

    public function up(Schema $schema): void
    {
        $webhook = $schema->getTable($this->getPrefixedTableName());
        if (!$webhook->hasColumn('marked_unhealthy_at')) {
            $webhook->addColumn('marked_unhealthy_at', Types::DATETIME_IMMUTABLE)
                ->setNotnull(false);
        }
        if (!$webhook->hasColumn('unhealthy_since')) {
            $webhook->addColumn('unhealthy_since', Types::DATETIME_IMMUTABLE)
                ->setNotnull(false);
        }
        if (!$webhook->hasColumn('last_notification_sent_at')) {
            $webhook->addColumn('last_notification_sent_at', Types::DATETIME_IMMUTABLE)
                ->setNotnull(false);
        }
    }

    public function down(Schema $schema): void
    {
        $webhook = $schema->getTable($this->getPrefixedTableName());
        if ($webhook->hasColumn('marked_unhealthy_at')) {
            $webhook->dropColumn('marked_unhealthy_at');
        }
        if ($webhook->hasColumn('unhealthy_since')) {
            $webhook->dropColumn('unhealthy_since');
        }
        if ($webhook->hasColumn('last_notification_sent_at')) {
            $webhook->dropColumn('last_notification_sent_at');
        }
    }
}
