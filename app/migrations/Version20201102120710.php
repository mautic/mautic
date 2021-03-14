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

final class Version20201102120710 extends AbstractMauticMigration
{
    private $table;
    private $index;

    public function preUp(Schema $schema): void
    {
        $this->table = $this->getTableName();
        $this->index = $this->generatePropertyName($this->table, 'idx', ['email_id']);

        $sql  = 'SHOW INDEX FROM '.$this->table.' WHERE Key_name = "'.$this->index.'"';
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        $indexExists = (bool) $stmt->fetch();
        $stmt->closeCursor();

        if (!$indexExists) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE '.$this->table.' DROP INDEX '.$this->index);
    }

    private function getTableName()
    {
        return $this->prefix.'email_list_xref';
    }
}
