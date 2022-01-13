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
     * @throws SkipMigration
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
        $categoryIdColumn = $schema->getTable("{$this->prefix}categories")->getColumn('id');
        if ($categoryIdColumn->getUnsigned()) {
            $this->addSql("ALTER TABLE {$this->prefix}lead_lists ADD category_id INT UNSIGNED DEFAULT NULL");
        } else {
            $this->addSql("ALTER TABLE {$this->prefix}lead_lists ADD category_id INT DEFAULT NULL");
        }

        $fkName  = $this->getFkName();
        $idxName = $this->getIdxName();

        $this->addSql(
            "ALTER TABLE {$this->prefix}lead_lists ADD CONSTRAINT $fkName FOREIGN KEY (category_id) REFERENCES {$this->prefix}categories (id) ON DELETE SET NULL"
        );
        $this->addSql("CREATE INDEX $idxName ON {$this->prefix}lead_lists (category_id)");
    }

    public function down(Schema $schema): void
    {
        $fkName  = $this->getFkName();
        $idxName = $this->getIdxName();

        $this->addSql("ALTER TABLE {$this->prefix}lead_lists DROP FOREIGN KEY $fkName");
        $this->addSql("ALTER TABLE {$this->prefix}lead_lists DROP INDEX $idxName");
        $this->addSql("ALTER TABLE {$this->prefix}lead_lists DROP category_id");
    }

    private function getFkName(): string
    {
        return $this->generatePropertyName('lead_lists', 'fk', ['category_id']);
    }

    private function getIdxName(): string
    {
        return $this->generatePropertyName('lead_lists', 'idx', ['category_id']);
    }
}
