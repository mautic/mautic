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

final class Version20200512125331 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigrationException
     */
    public function preUp(Schema $schema): void
    {
        $shouldRunMigration = false; // Please modify to your needs
        $table              = $schema->getTable($this->prefix.'forms');
        if (!$table->hasColumn('title')) {
            $shouldRunMigration = true;
        }
        if (!$shouldRunMigration) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE '.$this->prefix.'forms ADD translation_parent_id INT UNSIGNED DEFAULT NULL, ADD variant_parent_id INT UNSIGNED DEFAULT NULL, ADD title VARCHAR(191) NOT NULL, ADD lang VARCHAR(191) NOT NULL, ADD variant_settings LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', ADD variant_start_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', DROP no_index');
        $this->addSql('ALTER TABLE '.$this->prefix.'forms ADD CONSTRAINT FK_E6B105C9091A2FB FOREIGN KEY (translation_parent_id) REFERENCES devforms (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE '.$this->prefix.'forms ADD CONSTRAINT FK_E6B105C91861123 FOREIGN KEY (variant_parent_id) REFERENCES devforms (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_E6B105C9091A2FB ON '.$this->prefix.'forms (translation_parent_id)');
        $this->addSql('CREATE INDEX IDX_E6B105C91861123 ON '.$this->prefix.'forms (variant_parent_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE '.$this->prefix.'forms DROP FOREIGN KEY FK_E6B105C9091A2FB');
        $this->addSql('ALTER TABLE '.$this->prefix.'forms DROP FOREIGN KEY FK_E6B105C91861123');
        $this->addSql('DROP INDEX IDX_E6B105C9091A2FB ON '.$this->prefix.'forms');
        $this->addSql('DROP INDEX IDX_E6B105C91861123 ON '.$this->prefix.'forms');
        $this->addSql('ALTER TABLE '.$this->prefix.'forms ADD no_index TINYINT(1) DEFAULT NULL, DROP translation_parent_id, DROP variant_parent_id, DROP title, DROP lang, DROP variant_settings, DROP variant_start_date');
    }
}
