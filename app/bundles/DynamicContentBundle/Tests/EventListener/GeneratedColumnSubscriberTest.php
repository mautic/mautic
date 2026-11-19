<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\EventListener;

use Mautic\CoreBundle\Event\GeneratedColumnsEvent;
use Mautic\DynamicContentBundle\EventListener\GeneratedColumnSubscriber;
use PHPUnit\Framework\TestCase;

final class GeneratedColumnSubscriberTest extends TestCase
{
    private GeneratedColumnSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new GeneratedColumnSubscriber();
    }

    public function testGetSubscribedEvents(): void
    {
        $events = GeneratedColumnSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey('mautic.on_generated_columns_build', $events);
        $this->assertEquals(['onGeneratedColumnsBuild', 0], $events['mautic.on_generated_columns_build']);
    }

    public function testOnGeneratedColumnsBuild(): void
    {
        $event = new GeneratedColumnsEvent();

        $this->subscriber->onGeneratedColumnsBuild($event);

        $generatedColumn = $event->getGeneratedColumns()->current();

        $this->assertSame(MAUTIC_TABLE_PREFIX.'dynamic_content_stats', $generatedColumn->getTableName());
        $this->assertSame('source_data_uri', $generatedColumn->getColumnName());
        $this->assertSame('VARCHAR(4096) AS (JSON_UNQUOTE(JSON_EXTRACT(source_data, "$.uri"))) COMMENT \'(DC2Type:generated)\'', $generatedColumn->getColumnDefinition());
        $this->assertSame(['source_data_uri'], $generatedColumn->getIndexColumns());
        $this->assertSame('ADD INDEX `'.MAUTIC_TABLE_PREFIX.'source_data_uri`(source_data_uri(100))', $generatedColumn->getAddIndexSql());
    }
}
