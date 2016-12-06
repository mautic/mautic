<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Class Version20160712000000.
 */
class Version20160720000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // Clean out utm tags table for entries that don't have any utm tags
        $this->addSql("DELETE FROM {$this->prefix}lead_utmtags WHERE utm_campaign is null and utm_content is null and utm_medium is null and utm_source is null and utm_term is null");
    }
}
