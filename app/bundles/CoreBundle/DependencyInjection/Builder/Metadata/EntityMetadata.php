<?php

namespace Mautic\CoreBundle\DependencyInjection\Builder\Metadata;

use Mautic\CoreBundle\DependencyInjection\Builder\BundleMetadata;
use Mautic\CoreBundle\Entity\DeprecatedInterface;
use Symfony\Component\Finder\Finder;

class EntityMetadata
{
    /**
     * @var BundleMetadata
     */
    private $metadata;

    /**
     * @var array
     */
    private $ormConfig = [];

    /**
     * @var array
     */
    private $serializerConfig = [];

    public function __construct(BundleMetadata $metadata)
    {
        $this->metadata = $metadata;
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

        /** @var \SplFileInfo $file */
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
            if (empty($this->serializerConfig) && $reflectionClass->hasMethod('loadApiMetadata')) {
                $this->serializerConfig = [
                    'namespace_prefix' => $bundleNamespace.'\\Entity',
                    'path'             => "@$bundleName/Entity",
                ];
            }

            // The bundle leverages the static loadMetadata method
            if (empty($this->ormConfig) && $reflectionClass->hasMethod('loadMetadata')) {
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

    public function getOrmConfig(): array
    {
        return $this->ormConfig;
    }

    public function getSerializerConfig(): array
    {
        return $this->serializerConfig;
    }
}
