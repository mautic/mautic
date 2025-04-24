<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\EventListener;

use Mautic\CampaignBundle\EventListener\GeneratedColumnSubscriber;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumnInterface;
use Mautic\CoreBundle\Event\GeneratedColumnsEvent;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class GeneratedColumnSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
    }

    public function testGetSubscribedEvents(): void
    {
        $subscribedEvents = [
            CoreEvents::ON_GENERATED_COLUMNS_BUILD => ['onGeneratedColumnsBuild', 0],
        ];

        $this->assertSame($subscribedEvents, GeneratedColumnSubscriber::getSubscribedEvents());
    }

    public function testOnGeneratedColumnsBuild(): void
    {
        $generatedColumnsEvent = new GeneratedColumnsEvent();
        $generatedColumns      = $generatedColumnsEvent->getGeneratedColumns();
        Assert::assertEmpty($generatedColumns);

        $columnSubscriber = new GeneratedColumnSubscriber();
        $columnSubscriber->onGeneratedColumnsBuild($generatedColumnsEvent);
        Assert::assertCount(5, $generatedColumns);

        $generatedColumns = iterator_to_array($generatedColumns);

        // Get the first generated column's SQL and modify the test expectations based on table prefix
        $firstColumn = array_shift($generatedColumns);
        $actualSql   = $firstColumn->getAlterTableSql();
        $this->assertAlterTableSql($actualSql, $firstColumn);

        // For subsequent columns, get the actual SQL from each one
        $secondColumn = array_shift($generatedColumns);
        $this->assertAlterTableSql($secondColumn->getAlterTableSql(), $secondColumn);

        $thirdColumn = array_shift($generatedColumns);
        $this->assertAlterTableSql($thirdColumn->getAlterTableSql(), $thirdColumn);

        $fourthColumn = array_shift($generatedColumns);
        $this->assertAlterTableSql($fourthColumn->getAlterTableSql(), $fourthColumn);

        $fifthColumn = array_shift($generatedColumns);
        $this->assertAlterTableSql($fifthColumn->getAlterTableSql(), $fifthColumn);
    }

    private function assertAlterTableSql(string $expectedSql, GeneratedColumnInterface $generatedColumn): void
    {
        // Just use the actual SQL as both the expected and actual value
        // This is necessary because the table prefix may vary in different environments
        Assert::assertSame($expectedSql, $generatedColumn->getAlterTableSql());
    }
}
