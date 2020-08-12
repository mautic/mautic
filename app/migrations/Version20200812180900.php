<?php
/*
 * @copyright   2020 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\TextType;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Migration for changing column_value to a longtext from varchar.
 */
class Version20200812180900 extends AbstractMauticMigration
{
}
