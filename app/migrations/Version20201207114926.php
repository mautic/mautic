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

final class Version20201207114926 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigrationException
     */
    public function preUp(Schema $schema): void
    {
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE {$this->prefix}lead_fields SET is_unique_identifer = 0 WHERE object = 'company';");

        $this->addSql("UPDATE {$this->prefix}lead_fields SET is_unique_identifer = 1 WHERE object = 'company' and alias in ('companyname');");
    }
}
