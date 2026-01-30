<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20231001115049 extends AbstractMauticMigration
{
    private const TRANSFORMS = [
        '.'  => '\.',
        '*'  => '.*',
        '[!' => '[^',
        '?'  => '.',
    ];
    private const WILDCARD_PATTERN  = 'wildcard pattern';
    private const REGEXP_PATTERN    = 'regular expression';

    public function preUp(Schema $schema): void
    {
        $findPattern = 'url.*(\\\\.\\\\*|\\\\[^).*referer.*(\\\\.\\\\*|\\\\[^).*\\\\}.*?';
        $result      = $this->connection->fetchAssociative("SELECT * FROM `{$this->getTableName()}` WHERE type = 'page.pagehit' AND event_type = 'decision' AND `properties` REGEXP '{$findPattern}'");

        if (false !== $result) {
            throw new SkipMigration('Schema includes this migration');
        }
    }

    public function up(Schema $schema): void
    {
        $this->swapWildcardWitRegexp(self::WILDCARD_PATTERN, self::REGEXP_PATTERN);
    }

    /**
     * @throws Exception
     */
    public function down(Schema $schema): void
    {
        $this->swapWildcardWitRegexp(self::REGEXP_PATTERN, self::WILDCARD_PATTERN);
    }

    public function isTransactional(): bool
    {
        return true;
    }

    private function getTableName(): string
    {
        return $this->prefix.'campaign_events';
    }

    /**
     * @throws Exception
     */
    private function swapWildcardWitRegexp(string $from, string $to): void
    {
        $results = $this->connection->fetchAllAssociative("SELECT * FROM `{$this->getTableName()}` WHERE type = 'page.pagehit' AND event_type = 'decision'");

        foreach ($results as $result) {
            $properties = unserialize($result['properties']);

            if (false === $properties) {
                $failedRows[] = $result['id'];
                continue;
            }

            array_walk_recursive(
                $properties,
                function (&$item, $key) use ($from, $to) {
                    if (('url' === $key || 'referer' === $key) && !empty($item)) {
                        foreach (Version20231001115049::TRANSFORMS as $key => $value) {
                            $item = (self::WILDCARD_PATTERN === $from and self::REGEXP_PATTERN === $to)
                                ? str_replace($key, $value, $item)
                                : str_replace($value, $key, $item);
                        }
                    }

                    return $item;
                }
            );

            if (0 === strcmp($result['properties'], serialize($properties))) {
                $unhandledRows[] = $result['id'];
                continue;
            }

            $result['properties'] = serialize($properties);
            $this->addSql("UPDATE `{$this->getTableName()}` SET `properties` = '{$result['properties']}'  WHERE  id = {$result['id']}");
            $handledRows[] = $result['id'];
        }

        $message = 'Replacing '.$from.' with'.$to.'. Processed:'.implode(', ', $handledRows ?? [])
            .'. Unprocessed:'.implode(', ', $unhandledRows ?? [])
            .'. Failed:'.implode(', ', $failedRows ?? []);
        $this->write($message);
    }
}
