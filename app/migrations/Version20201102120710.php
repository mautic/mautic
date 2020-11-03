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
    private $table  = 'email_list_xref';
    private $index  = 'IDX_11DC9DF2A832C1C9';

    public function up(Schema $schema): void
    {
        if (!$schema->getTable($this->prefix.$this->table)->hasIndex($this->index)) {
            throw new SkipMigration('Schema includes this migration');
        }
        $this->addSql('ALTER TABLE '.$this->prefix.$this->table.' DROP INDEX '.$this->index);
    }
}
