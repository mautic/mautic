<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20201102164221 extends AbstractMauticMigration
{
    private const INDEX_NAME = 'message_lead_channel_channel_id';

    public function preUp(Schema $schema): void
    {
        $hasIndex = $schema->getTable($this->getTableName())
            ->hasIndex(self::INDEX_NAME);

        if ($hasIndex) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql(sprintf('CREATE INDEX %s ON %s (lead_id, channel, channel_id)', self::INDEX_NAME, $this->getTableName()));
    }

    private function getTableName(): string
    {
        return "{$this->prefix}message_queue";
    }
}
