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

final class Version20211108171716 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
        $table = $schema->getTable($this->prefix.'leads');
        if ($table->hasIndex('leads_id_email')) {
            throw new SkipMigration('Schema includes this migration');
        }

        parent::preUp($schema);
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX '.$this->prefix.'leads_id_email ON '.$this->prefix.'leads (id, email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX '.$this->prefix.'leads_id_email ON '.$this->prefix.'leads');
    }
}
