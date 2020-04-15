<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved.
 * @author      Mautic
 *
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20200415135706 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigrationException
     */
    public function preUp(Schema $schema): void
    {
        if ($schema->getTable("{$this->prefix}form_fields")->hasColumn('mapped_object')) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}form_fields 
            ADD mapped_object VARCHAR(191) DEFAULT NULL,
            ADD mapped_field VARCHAR(191) DEFAULT NULL");

        // All field that starts with company belongs to the company object.
        // Except the company field itself that belongs to the contact (lead) object.
        $this->addSql("UPDATE {$this->prefix}form_fields 
            SET mapped_object = CASE
                WHEN lead_field LIKE 'company%' AND lead_field != 'company' THEN 'company'
                ELSE 'lead'
            END, mapped_field = lead_field 
            WHERE lead_field IS NOT NULL");
    }
}
