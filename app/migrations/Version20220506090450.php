<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20220506090450 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
        $smsStatsTable = $schema->getTable(MAUTIC_TABLE_PREFIX.'sms_message_stats');
        if ($smsStatsTable->hasIndex("{$this->prefix}stat_sms_failed_search")) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE INDEX {$this->prefix}stat_sms_failed_search ON {$this->prefix}sms_message_stats (is_failed)");
    }
}
