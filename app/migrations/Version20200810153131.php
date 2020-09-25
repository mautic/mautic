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

final class Version20200810153131 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema): void
    {
        $accessTokenTable = $schema->getTable($this->prefix.'oauth2_accesstokens');
        if (!$accessTokenTable->getColumn('user_id')->getNotnull()) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        // Add column `role_id` in table `oauth2_clients`
        $oauth2ClientsTable = $schema->getTable("{$this->prefix}oauth2_clients");

        if (!$oauth2ClientsTable->hasColumn('role_id')) {
            $oauth2ClientsTable->addColumn('role_id', 'integer', ['unsigned' => true, 'notnull' => false]);
        }
        if (!$oauth2ClientsTable->hasForeignKey('FK_CLIENT_ROLE')) {
            $oauth2ClientsTable->addForeignKeyConstraint("{$this->prefix}roles", ['role_id'], ['id'], [], 'FK_CLIENT_ROLE');
        }

        if (!$oauth2ClientsTable->hasIndex('IDX_CLIENT_ROLE')) {
            $oauth2ClientsTable->addIndex(['role_id'], 'IDX_CLIENT_ROLE');
        }

        // Modify `user_id` column in `oauth2_accesstokens` table to allow null values
        $accessTokenTable = $schema->getTable("{$this->prefix}oauth2_accesstokens");
        $userIdColumn     = $accessTokenTable->getColumn('user_id');
        // Some of the instances still use signed id field in user table, and we have to respect that during migration.
        if (true === $userIdColumn->getUnsigned()) {
            $accessTokenTable->changeColumn('user_id', ['unsigned' => true, 'notnull' => false]);
        } else {
            $accessTokenTable->changeColumn('user_id', ['notnull' => false]);
        }
    }

    public function down(Schema $schema): void
    {
        // Remove column `role_id` from table `oauth2_clients` along with it's fk constraints and index
        $oauth2ClientsTable = $schema->getTable("{$this->prefix}oauth2_clients");
        $oauth2ClientsTable->removeForeignKey('FK_CLIENT_ROLE');
        $oauth2ClientsTable->dropIndex('IDX_CLIENT_ROLE');
        $oauth2ClientsTable->dropColumn('role_id');

        // Revert back column `user_id` in table `oauth2_accesstokens` to not allow null values
        $accessTokenTable = $schema->getTable("{$this->prefix}oauth2_accesstokens");
        // To go back to the state which client_credentials is not supported, we have to remove rows with
        // user_id is null and make the field NOT NULL-able
        $this->addSql("DELETE FROM {$this->prefix}oauth2_accesstokens WHERE user_id IS NULL");
        $accessTokenTable->changeColumn('user_id', ['unsigned' => true, 'notnull' => true]);
    }
}
