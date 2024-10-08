<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20240704164714 extends AbstractMauticMigration
{
    protected const TABLE_NAME = 'lead_fields';

    public function up(Schema $schema): void
    {
        $this->addSql(
            "UPDATE {$this->getPrefixedTableName()}
                  SET type = 'html',
                      properties = 'a:0:{}'
                  WHERE type = 'textarea' AND properties LIKE '%\"allowHtml\";s:1:\"1\"%'"
        );
    }
}
