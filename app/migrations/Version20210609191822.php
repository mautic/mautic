<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20210609191822 extends AbstractMauticMigration
{
    /**
     * Mautic 4 removed OAuth1 support, so we drop those tables.
     */
    public function up(Schema $schema): void
    {
        $schema->dropTable('oauth1_access_tokens');
        $schema->dropTable('oauth1_consumers');
        $schema->dropTable('oauth1_nonces');
        $schema->dropTable('oauth1_request_tokens');
    }
}
