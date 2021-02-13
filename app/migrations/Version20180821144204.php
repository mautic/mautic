<?php

declare(strict_types=1);

/*
 * @package     Mautic
 * @copyright   2018 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

class Version20180821144204 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema): void
    {
        if ($schema->getTable("{$this->prefix}webhook_queue")->hasIndex("{$this->prefix}webhook_id_date")) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE INDEX {$this->prefix}webhook_id_date ON {$this->prefix}webhook_queue (webhook_id, date_added)");
    }
}
