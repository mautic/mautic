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
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20210623071326 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE {$this->prefix}forms MODIFY post_action_property LONGTEXT ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE {$this->prefix}forms SET post_action_property = left(post_action_property,191)");
        $this->addSql("ALTER TABLE {$this->prefix}forms MODIFY post_action_property VARCHAR(191) DEFAULT NULL ");
    }
}
