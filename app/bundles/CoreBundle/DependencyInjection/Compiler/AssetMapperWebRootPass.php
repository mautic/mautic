<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AssetMapperWebRootPass implements CompilerPassInterface
{
    private const PUBLIC_ASSETS_PATH_RESOLVER_ID         = 'asset_mapper.public_assets_path_resolver';
    private const LOCAL_PUBLIC_ASSETS_FILESYSTEM_ID      = 'asset_mapper.local_public_assets_filesystem';
    private const COMPILED_ASSET_MAPPER_CONFIG_READER_ID = 'asset_mapper.compiled_asset_mapper_config_reader';
    private const DEFAULT_PUBLIC_PREFIX                  = '/assets/build/';

    public function process(ContainerBuilder $container): void
    {
        $webRoot = $this->resolveWebRoot($container);
        if (!$webRoot) {
            return;
        }

        if ($container->hasDefinition(self::LOCAL_PUBLIC_ASSETS_FILESYSTEM_ID)) {
            $container
                ->findDefinition(self::LOCAL_PUBLIC_ASSETS_FILESYSTEM_ID)
                ->replaceArgument(0, $webRoot);
        }

        if (!$container->hasDefinition(self::COMPILED_ASSET_MAPPER_CONFIG_READER_ID)) {
            return;
        }

        $publicPrefix = $this->resolvePublicPrefix($container);

        $container
            ->findDefinition(self::COMPILED_ASSET_MAPPER_CONFIG_READER_ID)
            ->replaceArgument(0, $webRoot.'/'.ltrim($publicPrefix, '/'));
    }

    private function resolveWebRoot(ContainerBuilder $container): ?string
    {
        if ($container->hasParameter('mautic.local_root')) {
            $localRoot = $container->getParameter('mautic.local_root');
            if (is_string($localRoot) && '' !== trim($localRoot) && !str_contains($localRoot, '%env(')) {
                return rtrim($localRoot, '/');
            }
        }

        if (!$container->hasParameter('kernel.project_dir')) {
            return null;
        }

        $projectDir = $container->getParameter('kernel.project_dir');
        if (!is_string($projectDir) || '' === trim($projectDir)) {
            return null;
        }

        $projectDir    = rtrim($projectDir, '/');
        $composerFile  = $projectDir.'/composer.json';
        $configuredDir = $this->resolveConfiguredPublicDir($composerFile, $container);

        if (null === $configuredDir || '' === $configuredDir || '.' === $configuredDir) {
            return $projectDir;
        }

        return $projectDir.'/'.trim($configuredDir, '/');
    }

    private function resolveConfiguredPublicDir(string $composerFile, ContainerBuilder $container): ?string
    {
        if (!is_file($composerFile)) {
            return null;
        }

        $container->addResource(new FileResource($composerFile));

        $contents = file_get_contents($composerFile);
        if (false === $contents) {
            return null;
        }

        $composerConfig = json_decode($contents, true);
        if (!is_array($composerConfig)) {
            return null;
        }

        $webRoot = $composerConfig['extra']['mautic-scaffold']['locations']['web-root'] ?? null;
        if (is_string($webRoot) && '' !== trim($webRoot)) {
            return $webRoot;
        }

        $publicDir = $composerConfig['extra']['public-dir'] ?? null;
        if (is_string($publicDir) && '' !== trim($publicDir)) {
            return $publicDir;
        }

        return null;
    }

    private function resolvePublicPrefix(ContainerBuilder $container): string
    {
        if (!$container->hasDefinition(self::PUBLIC_ASSETS_PATH_RESOLVER_ID)) {
            return self::DEFAULT_PUBLIC_PREFIX;
        }

        $publicPrefix = $container
            ->findDefinition(self::PUBLIC_ASSETS_PATH_RESOLVER_ID)
            ->getArgument(0);

        if (!is_string($publicPrefix) || '' === trim($publicPrefix)) {
            return self::DEFAULT_PUBLIC_PREFIX;
        }

        return $publicPrefix;
    }
}
