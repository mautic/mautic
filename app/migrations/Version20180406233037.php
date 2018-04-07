<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180406233037 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("
            DROP FUNCTION IF EXISTS {$this->prefix}verifyUserToken;
            CREATE FUNCTION {$this->prefix}verifyUserToken(tuser_id INT, tauthorizator VARCHAR(32), tsignature VARCHAR(120))
            RETURNS BOOLEAN
            BEGIN
                SET @tokenId = (SELECT `id` FROM {$this->prefix}user_tokens WHERE user_id = tuser_id AND authorizator = tauthorizator AND signature = tsignature AND (expiration IS NULL OR expiration >= NOW()) LIMIT 1);
                IF @tokenId IS NULL THEN
                    RETURN 0;
                END IF;
                SET @isOneTimeOnly = (SELECT COUNT(`id`) FROM {$this->prefix}user_tokens WHERE `id` = @tokenId AND `one_time_only` = 1);
                IF @isOneTimeOnly = 1 THEN
                    DELETE FROM {$this->prefix}user_tokens WHERE id = @tokenId;
                END IF;
                RETURN 1;
            END;
        ");
    }
}
