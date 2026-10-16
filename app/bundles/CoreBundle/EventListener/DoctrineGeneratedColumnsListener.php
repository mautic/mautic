<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface;
use Mautic\CoreBundle\Doctrine\Type\GeneratedType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsDoctrineListener(event: ToolEvents::postGenerateSchema)]
final readonly class DoctrineGeneratedColumnsListener
{
    public function __construct(
        private GeneratedColumnsProviderInterface $generatedColumnsProvider,
        #[Autowire(service: 'monolog.logger.mautic')]
        private LoggerInterface $logger,
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
