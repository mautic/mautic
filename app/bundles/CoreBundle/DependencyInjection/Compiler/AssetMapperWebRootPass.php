<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AssetMapperWebRootPass implements CompilerPassInterface
{
    private const PUBLIC_ASSETS_PATH_RESOLVER_ID         = 'asset_mapper.public_assets_path_resolver';
    private const LOCAL_PUBLIC_ASSETS_FILESYSTEM_ID      = 'asset_mapper.local_public_assets_filesystem';
    private const COMPILED_ASSET_MAPPER_CONFIG_READER_ID = 'asset_mapper.compiled_asset_mapper_config_reader';
    private const MAUTIC_LOCAL_ROOT                       = '%env(default:kernel.project_dir:resolve:MAUTIC_LOCAL_ROOT)%';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::LOCAL_PUBLIC_ASSETS_FILESYSTEM_ID)
            || !$container->hasDefinition(self::COMPILED_ASSET_MAPPER_CONFIG_READER_ID)
            || !$container->hasDefinition(self::PUBLIC_ASSETS_PATH_RESOLVER_ID)) {
            return;
        }

        $webRoot = $container->resolveEnvPlaceholders(self::MAUTIC_LOCAL_ROOT, true);
        $publicPrefix = $container
            ->getDefinition(self::PUBLIC_ASSETS_PATH_RESOLVER_ID)
            ->getArgument(0);

        if (!is_string($webRoot) || !is_string($publicPrefix)) {
            return;
        }

        $webRoot = rtrim($webRoot, '/');

        $container
            ->getDefinition(self::LOCAL_PUBLIC_ASSETS_FILESYSTEM_ID)
            ->replaceArgument(0, $webRoot);

        $container
            ->getDefinition(self::COMPILED_ASSET_MAPPER_CONFIG_READER_ID)
            ->replaceArgument(0, $webRoot.'/'.trim($publicPrefix, '/'));
    }
}
