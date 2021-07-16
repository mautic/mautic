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

final class Version20210623071326 extends AbstractMauticMigration
{
    /**
     * @throw SkipMigrationException
     */
    public function preUp(Schema $schema): void
    {
    }

    public function up(Schema $schema): void
    {
        // Changes remote_path and original_file_name columns to Longtext
        $this->addSql("ALTER TABLE {$this->prefix}forms MODIFY post_action_property LONGTEXT ");
    }

    public function down(Schema $schema): void
    {
        // Changes remote_path and original_file_name columns to earlier VARCHAR(191)
        // Rolling back causes any long URLs or Filenames with more than 191 chars to be trimmed.
        $this->addSql("UPDATE {$this->prefix}forms SET post_action_property = left(post_action,191)");
        $this->addSql("ALTER TABLE {$this->prefix}forms MODIFY post_action_property VARCHAR(191) DEFAULT '' ");
    }
}
