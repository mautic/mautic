<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\PageBundle\Token\Email\EmailStatToken;

final class Version20230406045219 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
    }

    public function up(Schema $schema): void
    {
        $confFile = dirname(__DIR__).'/config/local.php';

        if (!file_exists($confFile)) {
            throw new SkipMigration('No config/local.php file found, skipping this migration');
        }

        require $confFile;

        /** @phpstan-ignore-next-line */
        if (isset($parameters) && array_key_exists(EmailStatToken::FROM_EMAIL_ID_START_REPLACE_TOKENS, $parameters)) {
            throw new SkipMigration(sprintf('Parameter %s already exist in config/local.php, skipping this migration', EmailStatToken::FROM_EMAIL_ID_START_REPLACE_TOKENS));
        }
        $sql = <<<SQL
            SELECT id
            FROM {$this->prefix}emails
            ORDER BY id DESC
SQL;

        $stmt        = $this->connection->prepare($sql);
        $lastEmailId = $stmt->executeQuery()->fetchOne();
        if ($lastEmailId) {
            $parameters[EmailStatToken::FROM_EMAIL_ID_START_REPLACE_TOKENS] = $lastEmailId;
            // Write updated config to local.php
            $result = file_put_contents($confFile, "<?php\n".'$parameters = '.var_export($parameters, true).';');

            if (false === $result) {
                throw new \Exception(sprintf("Couldn't update configuration file to add the %s parameter", EmailStatToken::FROM_EMAIL_ID_START_REPLACE_TOKENS));
            }
        }
    }
}
