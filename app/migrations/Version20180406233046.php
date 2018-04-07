<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180406233046 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("
            DROP FUNCTION IF EXISTS {$this->prefix}signUserToken;
            CREATE FUNCTION {$this->prefix}signUserToken(tuser_id INT, tauthorizator VARCHAR(32), tsignature_length INT, texpiration DATETIME, tone_time_only BOOLEAN)
            RETURNS VARCHAR(120)
            BEGIN
              IF tsignature_length IS NULL THEN
                SET tsignature_length = 120;
              END IF;
              SET @randomSignature = NULL;
              SET @randomSignatureTaken = 0;
              WHILE @randomSignature IS NULL OR @randomSignatureTaken = 1 DO
                SET @randomSignature = SUBSTRING(SHA2(CONCAT(NOW(), RAND(), UUID()), 512), 1, tsignature_length - 1);
                SET @existingToken = (SELECT `id` FROM `user_tokens` WHERE `signature` = @randomSignature LIMIT 1);
                IF @existingToken IS NULL THEN
                  SET @randomSignatureTaken = 0;
                ELSE
                  SET @randomSignatureTaken = 1;
                END IF;
              END WHILE;
              INSERT INTO `{$this->prefix}user_tokens` (`user_id`, `authorizator`, `signature`, `expiration`, `one_time_only`) VALUES (tuser_id, tauthorizator, @randomSignature, texpiration, tone_time_only);  
              RETURN @randomSignature;
            END;
        ");
    }
}
