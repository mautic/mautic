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
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20201102120710 extends AbstractMauticMigration
{
    private $table  = 'email_list_xref';
    private $column = 'email_id';

    public function up(Schema $schema): void
    {
        /** @var QueryBuilder $query */
        $query     = $this->container->get('doctrine')->getConnection()->createQueryBuilder();
        $indexName = $query->select('INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema="'.$schema->getName().'" AND table_name="'.$this->prefix.$this->table.'" AND column_name="'.$this->column.'" AND INDEX_NAME != "PRIMARY"')->execute()->fetchColumn();

        if (!empty($indexName)) {
            $this->addSql('ALTER TABLE '.$this->prefix.$this->table.' DROP INDEX '.$indexName);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE '.$this->prefix.$this->table.' ADD INDEX IDX_'.strtoupper($this->column).' ('.$this->column.')');
    }
}
