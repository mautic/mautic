<?php

declare(strict_types=1);

/*
 * @copyright   <year> Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20211121211138 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
    }

    public function up(Schema $schema): void
    {
        // Add schedule fields
        $this->addSql("ALTER TABLE {$this->prefix}reports ADD schedule_time VARCHAR(191) DEFAULT '00:00' NOT NULL, ADD schedule_timezone VARCHAR(191) DEFAULT 'UTC' NOT NULL, ADD send_empty TINYINT(1) DEFAULT '0' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}reports DROP COLUMN schedule_time, DROP COLUMN schedule_timezone, DROP COLUMN send_empty ");
    }
}
