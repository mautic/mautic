<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Helper\EmojiHelper;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\EmailBundle\Entity\Copy;
use Mautic\EmailBundle\Entity\Email;

final class Version20201026101117 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigration
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
        $entityManager  = $this->container->get('doctrine.orm.entity_manager');
        \assert($entityManager instanceof EntityManagerInterface);
        $batchSize      = 50;
        $i              = 1;
        $q              = $entityManager->createQuery("SELECT t from {$entityClass} t");
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
