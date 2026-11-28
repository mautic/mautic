<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Moves stored Philippine provinces to the names now used in regions.json.
 */
final class Version20261128120000 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        $oldAndNewValues = [
            'Batasn'             => 'Bataan',
            'Compostela Valley'  => 'Davao de Oro',
            'Davao'              => 'Davao del Norte',
            'Kalinga-Apayso'     => 'Kalinga',
            'Negroe Occidental'  => 'Negros Occidental',
            'Zamboanga Sibiguey' => 'Zamboanga Sibugay',
        ];

        foreach ($oldAndNewValues as $old => $new) {
            $old = $this->connection->quote($old);
            $new = $this->connection->quote($new);
            $this->addSql("UPDATE `{$this->prefix}leads` SET `state` = {$new} WHERE `state` = {$old}");
            $this->addSql("UPDATE `{$this->prefix}companies` SET `companystate` = {$new} WHERE `companystate` = {$old}");
        }
    }
}
