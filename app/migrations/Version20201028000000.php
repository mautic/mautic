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

/**
 * This migration is to avoid MySQL crash issues like https://backlog.acquia.com/browse/MAUT-4513.
 */
final class Version20201028000000 extends AbstractMauticMigration
{
    /**
     * @var string
     */
    protected static $tableName = 'emails_draft';

    public function up(Schema $schema): void
    {
        $fkName     = $this->generatePropertyName('emails_draft', 'fk', ['email_id']);
        $this->addSql(
            sprintf(
                'ALTER TABLE %s DROP FOREIGN KEY %s',
                $this->getPrefixedTableName(),
                $fkName
            )
        );
        $this->addSql(
            sprintf(
                'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (email_id) REFERENCES %s (id);',
                $this->getPrefixedTableName(),
                $fkName,
                $this->getPrefixedTableName('emails')
            )
        );
    }
}
