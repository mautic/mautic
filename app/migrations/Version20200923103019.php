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

final class Version20200923103019 extends AbstractMauticMigration
{
    public function getDescription(): string
    {
        return 'Add is_soft column to the email_stats table';
    }

    /**
     * @throws SkipMigrationException
     */
    public function preUp(Schema $schema) :void
    {
        if ($schema->getTable($this->prefix.'email_stats')->hasColumn('is_soft')) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema) :void
    {
        $this->addSql("ALTER TABLE ".$this->prefix."email_stats ADD is_soft BOOLEAN DEFAULT 0 NOT NULL");
    }
}
