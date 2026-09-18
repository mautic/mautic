<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Helper\EmojiHelper;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\EmailBundle\Entity\Copy;
use Mautic\EmailBundle\Entity\Email;

final class Version20201026101117 extends AbstractMauticMigration
{
    private string $tableEmails = 'emails';
    private string $tableCopies = 'email_copies';

    /**
     * @throws SkipMigration
     */
    public function preUp(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        $table = $schema->getTable($this->prefix.$this->tableEmails);

        $subjectColumn = $table->getColumn('subject');

        if (DatabasePlatform::isPostgreSQL($platform)) {
            // PostgreSQL: safe check for collation (may not be in platformOptions)
            $collation = $subjectColumn->hasPlatformOption('collation') ? $subjectColumn->getPlatformOption('collation') : null;
            if ($collation && str_starts_with($collation, 'utf8')) {
                throw new SkipMigration('Migration already applied (UTF8 collation detected)');
            }

            // If no collation set → assume default is sufficient for emoji support
            // You can also skip if no collation key exists (common case)
            if (null === $collation) {
                // For most PG installs default is utf8-compatible → can skip
                throw new SkipMigration('No specific collation → already compatible');
            }
        } else {
            // MySQL/MariaDB: original charset check
            $charset = $subjectColumn->getPlatformOption('charset') ?? null;
            if ('utf8mb4' === $charset) {
                throw new SkipMigration('Migration already applied (utf8mb4 charset detected)');
            }
        }
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        $tables = [
            $this->tableEmails => ['subject', 'custom_html', 'plain_text', 'name'],
            $this->tableCopies => ['subject', 'body', 'body_text'],
        ];

        foreach ($tables as $table => $columns) {
            $fullTable = $this->prefix.$table;

            foreach ($columns as $column) {
                if (DatabasePlatform::isPostgreSQL($platform)) {
                    // PostgreSQL: set collation to a utf8-compatible one
                    // "utf8_general_ci" is common and supports emojis
                    // Use quoted identifier if needed
                    $this->addSql("
                        ALTER TABLE {$fullTable}
                        ALTER COLUMN {$column} TYPE text COLLATE \"utf8_general_ci\"
                    ");
                } else {
                    // MySQL / MariaDB
                    $this->addSql("
                        ALTER TABLE {$fullTable}
                        CHANGE {$column} {$column} LONGTEXT
                        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
                    ");
                }
            }
        }
    }

    public function postUp(Schema $schema): void
    {
        $this->convertEmailsEmojies();
        $this->convertEmailCopiesEmojies();
        $this->convertDynamicContentEmojies();
    }

    private function convertEmailsEmojies(): void
    {
        $this->iterateOverAllEntities(
            Email::class,
            function (Email $email): void {
                $email->setName(EmojiHelper::toEmoji($email->getName(), 'short'));
                $email->setSubject(EmojiHelper::toEmoji($email->getSubject(), 'short'));
                $email->setCustomHtml(EmojiHelper::toEmoji($email->getCustomHtml(), 'short'));
                $email->setPlainText(EmojiHelper::toEmoji($email->getPlainText(), 'short'));
            }
        );
    }

    private function convertEmailCopiesEmojies(): void
    {
        $this->iterateOverAllEntities(
            Copy::class,
            function (Copy $emailCopy): void {
                $emailCopy->setSubject(EmojiHelper::toEmoji($emailCopy->getSubject(), 'short'));
                $emailCopy->setBody(EmojiHelper::toEmoji($emailCopy->getBody(), 'short'));
                $emailCopy->setBodyText(EmojiHelper::toEmoji($emailCopy->getBodyText(), 'short'));
            }
        );
    }

    private function convertDynamicContentEmojies(): void
    {
        $this->iterateOverAllEntities(
            DynamicContent::class,
            function (DynamicContent $dynamicContent): void {
                $dynamicContent->setDescription(EmojiHelper::toEmoji($dynamicContent->getDescription(), 'short'));
            }
        );
    }

    private function iterateOverAllEntities(string $entityClass, callable $entityModifier): void
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        \assert($entityManager instanceof EntityManagerInterface);

        $batchSize = 50;
        $i         = 1;

        $q              = $entityManager->createQuery("SELECT t FROM {$entityClass} t");
        $iterableResult = $q->toIterable();

        foreach ($iterableResult as $row) {
            $entityModifier($row[0]);
            $entityManager->persist($row[0]);

            if (0 === ($i % $batchSize)) {
                $entityManager->flush();
                $entityManager->clear();
            }
            ++$i;
        }

        $entityManager->flush();
    }
}
