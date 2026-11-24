<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\Bundle;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\PluginBundle\Bundle\PluginDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PluginDatabaseTest extends TestCase
{
    public function testUsesConfiguredTablePrefix(): void
    {
        $this->assertSame('mautic_', $this->getTablePrefix(new PluginDatabase(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(Connection::class),
            'mautic_'
        )));
    }

    #[DataProvider('emptyPrefixProvider')]
    public function testFallsBackToTablePrefixConstant(?string $configuredPrefix): void
    {
        $this->assertSame(MAUTIC_TABLE_PREFIX, $this->getTablePrefix(new PluginDatabase(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(Connection::class),
            $configuredPrefix
        )));
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function emptyPrefixProvider(): iterable
    {
        yield 'empty string' => [''];
        yield 'null' => [null];
    }

    private function getTablePrefix(PluginDatabase $pluginDatabase): string
    {
        return (new \ReflectionProperty(PluginDatabase::class, 'mauticDbPrefix'))->getValue($pluginDatabase);
    }
}
