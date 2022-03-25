<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Migration;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Mautic\IntegrationsBundle\Exception\PathNotFoundException;

class Engine
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var string
     */
    private $tablePrefix;

    /**
     * @var string
     */
    private $migrationsPath;

    /**
     * @var string
     */
    private $bundleName;

    public function __construct(EntityManager $entityManager, string $tablePrefix, string $pluginPath, string $bundleName)
    {
        $this->entityManager  = $entityManager;
        $this->tablePrefix    = $tablePrefix;
        $this->migrationsPath = $pluginPath.'/Migrations/';
        $this->bundleName     = $bundleName;
    }

    /**
     * Run available migrations.
     */
    public function up(): void
    {
        try {
            $migrationClasses = $this->getMigrationClasses();
        } catch (PathNotFoundException $e) {
            return;
        }

        if (!$migrationClasses) {
            return;
        }

        $this->entityManager->beginTransaction();

        try {
            foreach ($migrationClasses as $migrationClass) {
                /** @var AbstractMigration $migration */
                $migration = new $migrationClass($this->entityManager, $this->tablePrefix);

                if ($migration->shouldExecute()) {
                    $migration->execute();
                }
            }

            $this->entityManager->commit();
        } catch (DBALException $e) {
            $this->entityManager->rollback();

            throw $e;
        }
    }

    /**
     * Get migration classes to proceed.
     *
     * @return string[]
     */
    private function getMigrationClasses(): array
    {
        $migrationFileNames = $this->getMigrationFileNames();
        $migrationClasses   = [];

        foreach ($migrationFileNames as $fileName) {
            require_once $this->migrationsPath.$fileName;
            $className          = preg_replace('/\\.[^.\\s]{3,4}$/', '', $fileName);
            $className          = 'MauticPlugin\\'.$this->bundleName."\Migrations\\${className}";
            $migrationClasses[] = $className;
        }

        return $migrationClasses;
    }

    /**
     * Get migration file names.
     *
     * @return string[]
     */
    private function getMigrationFileNames(): array
    {
        $fileNames = @scandir($this->migrationsPath);

        if (false === $fileNames) {
            throw new PathNotFoundException(sprintf("'%s' directory not found", $this->migrationsPath));
        }

        return array_diff($fileNames, ['.', '..']);
    }
}
