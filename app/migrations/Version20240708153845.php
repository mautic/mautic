<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20240708153845 extends AbstractMauticMigration
{
    private string $emailStatsTableName;

    protected string $emailsTableName;

    public function preUp(Schema $schema): void
    {
        $this->emailStatsTableName = $this->prefix.'email_stats';
        $this->emailsTableName     = $this->prefix.'emails';
    }

    public function up(Schema $schema): void
    {
        $sql          = sprintf('SELECT id, read_count FROM %s', $this->emailsTableName);
        $emailsResult = $this->connection->executeQuery($sql)->fetchAllAssociative();

        foreach ($emailsResult as $email) {
            $statsResult = $this->connection
              ->executeQuery(
                  "SELECT email_id, is_read 
                        FROM {$this->emailStatsTableName} 
                        WHERE email_id = {$email['id']}"
              )
              ->fetchAllAssociative();
            $totalReadCount = 0;
            foreach ($statsResult as $stats) {
                $totalReadCount += (int) $stats['is_read'];
            }
            if ((int) $email['read_count'] < $totalReadCount) {
                $this
                  ->addSql(
                      "UPDATE {$this->emailsTableName} 
                            SET read_count = '{$totalReadCount}' 
                            WHERE id = '{$email['id']}'"
                  );
            }
        }
    }
}
