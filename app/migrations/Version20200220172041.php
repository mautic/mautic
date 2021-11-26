<?php

/*
 * @package     Mautic
 * @copyright   2020 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200220172041 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE `{$this->prefix}categories` SET bundle = 'messages' WHERE bundle = '0';");
    }
}
