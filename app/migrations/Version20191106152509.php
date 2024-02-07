<?php

/*
 * @package     Mautic
 * @copyright   2019 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

class Version20191106152509 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE {$this->prefix}lead_fields
	        SET `char_length_limit` = NULL 
	        WHERE `type` NOT IN ('text', 'select', 'multiselect', 'phone', 'url', 'email')
	        AND `char_length_limit` IS NOT NULL;
        ");
    }
}
