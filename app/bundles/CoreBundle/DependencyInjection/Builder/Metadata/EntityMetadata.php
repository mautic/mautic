<?php

namespace Mautic\CoreBundle\DependencyInjection\Builder\Metadata;

use Doctrine\ORM\Mapping\Embeddable;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Mautic\CoreBundle\DependencyInjection\Builder\BundleMetadata;
use Mautic\CoreBundle\Entity\DeprecatedInterface;
use Symfony\Component\Finder\Finder;

final class EntityMetadata
{
    private array $ormConfig = [];

    private array $serializerConfig = [];

    public function __construct(
        private readonly BundleMetadata $metadata,
    ) {
    }

    public function build(): void
    {
        // Check for staticphp mapping
        $entityDirectory = $this->metadata->getDirectory().'/Entity';
        if (!file_exists($entityDirectory)) {
            return;
        }

        $finder = Finder::create()
            ->name('*.php')
            ->notName('*Repository.php')
            ->in($entityDirectory);

        $bundleNamespace = $this->metadata->getNamespace();
        $bundleName      = $this->metadata->getBundleName();

        foreach ($finder as $file) {
            // Check to see if entities are organized by subfolder
            $subFolder = $file->getRelativePath() ? $file->getRelativePath().'\\' : '';
            $fileName  = basename($file->getFilename(), '.php');

            // Just check first file for the loadMetadata function
            $className       = sprintf('\\%s\\Entity\\%s%s', $bundleNamespace, $subFolder, $fileName);
            $reflectionClass = new \ReflectionClass($className);

            if ($reflectionClass->implementsInterface(DeprecatedInterface::class)) {
                // Ignore this interface as it is extending another for BC purposes
                continue;
            }

            // The bundle leverages the static loadApiMetadata method
            if ([] === $this->serializerConfig && $reflectionClass->hasMethod('loadApiMetadata')) {
                $this->serializerConfig = [
                    'namespace_prefix' => $bundleNamespace.'\\Entity',
                    'path'             => "@{$bundleName}/Entity",
                ];
            }

            // The bundle maps entities via the static loadMetadata method or Doctrine attributes;
            // the staticphp driver is swapped for one that reads both (DoctrineMappingDriverPass)
            if ([] === $this->ormConfig && ($reflectionClass->hasMethod('loadMetadata') || $this->hasOrmMappingAttribute($reflectionClass))) {
                $this->ormConfig = [
                    'dir'       => 'Entity',
                    'type'      => 'staticphp',
                    'prefix'    => $bundleNamespace.'\\Entity',
                    'mapping'   => true,
                    'is_bundle' => true,
                ];
            }
        }
    }

    private function hasOrmMappingAttribute(\ReflectionClass $reflectionClass): bool
    {
        foreach ([Entity::class, MappedSuperclass::class, Embeddable::class] as $ormAttribute) {
            if ([] !== $reflectionClass->getAttributes($ormAttribute)) {
                return true;
            }
        }

        return false;
    }

    public function getOrmConfig(): array
    {
        return $this->ormConfig;
    }

    public function getSerializerConfig(): array
    {
        return $this->serializerConfig;
    }
}
