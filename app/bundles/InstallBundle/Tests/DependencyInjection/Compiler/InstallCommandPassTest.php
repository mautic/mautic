<?php

declare(strict_types=1);

namespace Mautic\InstallBundle\Tests\DependencyInjection\Compiler;

use Mautic\CoreBundle\EventListener\DoctrineEventsSubscriber;
use Mautic\InstallBundle\DependencyInjection\Compiler\InstallCommandPass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class InstallCommandPassTest extends TestCase
{
    #[DataProvider('providePrefixes')]
    public function testInstallerCompilerPassUsesTheExplicitPrefixAndCurrentServices(string $prefix): void
    {
        $args = $_SERVER['argv'];
        $_SERVER['argv'] = ['bin/console', 'mautic:install', 'http://localhost', '--db_table_prefix='.$prefix];
        $container = new ContainerBuilder();
        $container->register(DoctrineEventsSubscriber::class);
        $container->register('mautic.schema.helper.column');
        $container->register('mautic.schema.helper.index');

        try {
            (new InstallCommandPass())->process($container);
        } finally {
            $_SERVER['argv'] = $args;
        }

        $this->assertSame($prefix, $container->getParameter('mautic.db_table_prefix'));
        $this->assertSame($prefix, $container->getDefinition(DoctrineEventsSubscriber::class)->getArgument('$tablePrefix'));
        $this->assertSame($prefix, $container->getDefinition('mautic.schema.helper.column')->getArgument('$prefix'));
        $this->assertSame($prefix, $container->getDefinition('mautic.schema.helper.index')->getArgument('$prefix'));
    }

    public static function providePrefixes(): iterable
    {
        yield ['pgcli_'];
        yield [''];
    }
}
