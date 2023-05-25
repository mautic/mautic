<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20230525202700 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
        $shouldRunMigration = true;

        if (!$shouldRunMigration) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $oldAndNewValues = [
            'Niederosterreich'   => 'Niederösterreich',
            'Oberosterreich'     => 'Oberösterreich',
            'Geneva'             => 'Genève',
            'Graubunden'         => 'Graubünden',
            'Neuchatel'          => 'Neuchâtel',
            'Sankt Gallen'       => 'St. Gallen',
            'Zurich'             => 'Zürich',
            'Baden-Wuerttemberg' => 'Baden-Württemberg',
            'Thueringen'         => 'Thüringen',
        ];

        foreach ($oldAndNewValues as $old => $new) {
            $this->addSql("UPDATE `{$this->prefix}leads` SET `state` = '{$new}' WHERE `state` = '{$old}'");
        }
    }
}
