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
            $totalCountResult = $this->connection
              ->executeQuery(
                  "SELECT email_id, SUM(is_read) as total_read_count 
                        FROM {$this->emailStatsTableName} 
                        WHERE email_id = :email_id 
                        GROUP BY email_id",
                  ['email_id' => $email['id']]
              )
              ->fetchAssociative();
            if (is_array($totalCountResult) && $email['id'] === $totalCountResult['email_id'] && (int) $email['read_count'] < $totalCountResult['total_read_count']) {
                $this
                  ->addSql(
                      "UPDATE {$this->emailsTableName} 
                            SET read_count = '{$totalCountResult['total_read_count']}' 
                            WHERE id = '{$email['id']}'"
                  );
            }
        }
    }
}
