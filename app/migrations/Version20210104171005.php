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

final class Version20210104171005 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigrationException
     */
    public function preUp(Schema $schema): void
    {
        $table = $schema->getTable($this->prefix.'lead_lists');

        if ($table->hasColumn('category_id')) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}lead_lists ADD category_id INT UNSIGNED DEFAULT NULL");
        $this->addSql("ALTER TABLE {$this->prefix}lead_lists ADD CONSTRAINT FK_6EC1522A12469DE2 FOREIGN KEY (category_id) REFERENCES {$this->prefix}categories (id) ON DELETE SET NULL");
        $this->addSql("CREATE INDEX IDX_6EC1522A12469DE2 ON {$this->prefix}lead_lists (category_id)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}lead_lists DROP FOREIGN KEY FK_6EC1522A12469DE2");
        $this->addSql("ALTER TABLE {$this->prefix}lead_lists DROP INDEX IDX_6EC1522A12469DE2");
        $this->addSql("ALTER TABLE {$this->prefix}lead_lists DROP category_id");
    }
}
