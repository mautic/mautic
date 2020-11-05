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
use Mautic\CoreBundle\Entity\Notification;
use Mautic\CoreBundle\Helper\EmojiHelper;

final class Version20201105120328 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigrationException
     */
    public function preUp(Schema $schema): void
    {
        $table = $schema->getTable($this->prefix.'notifications');

        if ('utf8mb4' === $table->getColumn('header')->getPlatformOption('charset')) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $tables = [
            'notifications' => [
                ['name' => 'header',  'type' => 'VARCHAR(512)'],
                ['name' => 'message', 'type' => 'LONGTEXT'],
            ],
            'push_notifications' => [
                ['name' => 'name',        'type' => 'VARCHAR(255)'],
                ['name' => 'description', 'type' => 'LONGTEXT'],
                ['name' => 'heading',     'type' => 'LONGTEXT'],
                ['name' => 'message',     'type' => 'LONGTEXT'],
                ['name' => 'button',      'type' => 'LONGTEXT'],
            ],
        ];

        foreach ($tables as $table => $columns) {
            foreach ($columns as $column) {
                $this->addSql("ALTER TABLE {$this->prefix}{$table} 
                    CHANGE {$column['name']} {$column['name']} {$column['type']} 
                    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
        }
    }

    /**
     * The EmojiHelper was used only for the CoreBundle's Notifications.
     * The Push Notifications doesn't have to be converted.
     */
    public function postUp(Schema $schema): void
    {
        $this->convertNotificationEmojies();
    }

    private function convertNotificationEmojies(): void
    {
        $this->iterateOverAllEntities(
            Notification::class,
            function (Notification $notification) {
                $notification->setHeader(EmojiHelper::toEmoji($notification->getHeader(), 'short'));
                $notification->setMessage(EmojiHelper::toEmoji($notification->getMessage(), 'short'));
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
