<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\LeadBundle\Entity\LeadEventLog;

final class Version20201125155904 extends AbstractMauticMigration
{
    /**
     * @var string
     */
    private const TABLE_NAME = 'lead_event_log';

    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
        $this->skipIf(
            empty($this->prefix),
            'This instance does not use prefix, so this migration does not apply. Skipping the migration'
        );

        $this->skipIf(
            $schema->getTable($this->getTableName())->hasIndex($this->getIndexWithPrefix()),
            sprintf('Index %s already exists. Skipping the migration', $this->getIndexWithPrefix())
        );

        $this->skipIf(
            !$schema->getTable($this->getTableName())->hasIndex(LeadEventLog::INDEX_SEARCH),
            sprintf('Index %s does not exist. Skipping the migration', LeadEventLog::INDEX_SEARCH)
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            sprintf(
                'ALTER TABLE `%s` RENAME INDEX `%s` TO `%s`',
                $this->getTableName(),
                LeadEventLog::INDEX_SEARCH,
                $this->getIndexWithPrefix()
            )
        );
    }

    private function getTableName(): string
    {
        return $this->prefix.self::TABLE_NAME;
    }

    private function getIndexWithPrefix(): string
    {
        return $this->prefix.LeadEventLog::INDEX_SEARCH;
    }
}
