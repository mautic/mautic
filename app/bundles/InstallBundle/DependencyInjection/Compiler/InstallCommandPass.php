<?php

declare(strict_types=1);

namespace Mautic\InstallBundle\DependencyInjection\Compiler;

use Mautic\InstallBundle\Command\InstallCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class InstallCommandPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $args = $_SERVER['argv'] ?? [];

        if (!in_array(InstallCommand::COMMAND, $args, true)) {
            return;
        }

        $input       = new ArgvInput($args);
        $tablePrefix = $input->hasOption('db_table_prefix')
            ? $input->getOption('db_table_prefix')
            : MAUTIC_TABLE_PREFIX;

        if (!$tablePrefix) {
            return;
        }

        $container->setParameter('mautic.db_table_prefix', $tablePrefix);
        $container->getDefinition('mautic.tblprefix_subscriber')->setArgument('$tablePrefix', $tablePrefix);
        $container->getDefinition('mautic.schema.helper.column')->setArgument('$prefix', $tablePrefix);
        $container->getDefinition('mautic.schema.helper.index')->setArgument('$prefix', $tablePrefix);
    }
}
