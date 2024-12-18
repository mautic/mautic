<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\EventListener;

use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface;
use Mautic\CoreBundle\Doctrine\Type\GeneratedType;
use Psr\Log\LoggerInterface;

class DoctrineGeneratedColumnsListener
{
    public function __construct(
        protected GeneratedColumnsProviderInterface $generatedColumnsProvider,
        protected LoggerInterface $logger,
    ) {
    }

    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $schema           = $args->getSchema();
        $generatedColumns = $this->generatedColumnsProvider->getGeneratedColumns();

        foreach ($generatedColumns as $generatedColumn) {
            try {
                if (!$schema->hasTable($generatedColumn->getTableName())) {
                    continue;
                }

                $table = $schema->getTable($generatedColumn->getTableName());

                if ($table->hasColumn($generatedColumn->getColumnName())) {
                    continue;
                }

                $table->addColumn(
                    $generatedColumn->getColumnName(),
                    GeneratedType::GENERATED,
                    [
                        'columnDefinition' => $generatedColumn->getColumnDefinition(),
                        'notNull'          => false,
                    ]
                );

                $table->addIndex($generatedColumn->getIndexColumns(), $generatedColumn->getIndexName());
            } catch (\Exception $e) {
                // table doesn't exist or something bad happened so oh well
                $this->logger->error('SCHEMA ERROR: '.$e->getMessage());
            }
        }
    }
}
