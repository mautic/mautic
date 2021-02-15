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

final class Version20210115065034 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigrationException
     */
    public function preUp(Schema $schema): void
    {
        $table = $schema->getTable($this->prefix.'emails');

        if ($table->hasColumn('preheader_text')) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}emails ADD `preheader_text` LONGTEXT NULL AFTER `subject`");
    }
}
