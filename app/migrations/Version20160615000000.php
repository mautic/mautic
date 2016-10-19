<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
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
 * Class Version20160615000000.
 */
class Version20160615000000 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->getTable($this->prefix.'email_stats');
        if ($table->hasIndex($this->prefix.'email_date_read')) {
            $this->addSql('DROP INDEX '.$this->prefix.'email_date_read ON '.$this->prefix.'email_stats');
        }

        $this->addSql('CREATE INDEX '.$this->prefix.'email_date_read ON '.$this->prefix.'email_stats (date_read)');
    }
}
