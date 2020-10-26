<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Helper\EmojiHelper;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\EmailBundle\Entity\Copy;
use Mautic\EmailBundle\Entity\Email;

final class Version20201026101117 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigrationException
     */
    public function preUp(Schema $schema): void
    {
        $table = $schema->getTable($this->prefix.'emails');

        if ('utf8mb4' === $table->getColumn('subject')->getPlatformOption('charset')) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        // Note: all these columns are type of LONGTEXT.
        $tables = [
            'emails'       => ['subject', 'custom_html', 'plain_text', 'name'],
            'email_copies' => ['subject', 'body', 'body_text'],
        ];

        foreach ($tables as $table => $columns) {
            foreach ($columns as $column) {
                $this->addSql("ALTER TABLE {$this->prefix}{$table} CHANGE {$column} {$column} LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
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
            function (Email $email) {
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
            function (Copy $emailCopy) {
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
            function (DynamicContent $dynamicContent) {
                $dynamicContent->setDescription(EmojiHelper::toEmoji($dynamicContent->getDescription(), 'short'));
            }
        );
    }

    private function iterateOverAllEntities(string $entityClass, callable $entityModifier): void
    {
        $batchSize      = 50;
        $i              = 1;
        $q              = $this->entityManager->createQuery("SELECT t from {$entityClass} t");
        $iterableResult = $q->iterate();
        foreach ($iterableResult as $row) {
            $entityModifier($row[0]);
            $this->entityManager->persist($row[0]);

            if (0 === ($i % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
            ++$i;
        }
        $this->entityManager->flush();
    }
}
