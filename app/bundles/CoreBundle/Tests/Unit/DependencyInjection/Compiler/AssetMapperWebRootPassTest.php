<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\DependencyInjection\Compiler;

use Mautic\CoreBundle\DependencyInjection\Compiler\AssetMapperWebRootPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AssetMapperWebRootPassTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/mautic_asset_mapper_webroot_'.uniqid();
        mkdir($this->projectDir);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->projectDir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->projectDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->projectDir);
    }

    public function testAssetMapperArgumentsAreRebasedToConfiguredLocalRoot(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->projectDir);
        $container->setParameter('mautic.local_root', $this->projectDir.'/docroot');

        $container
            ->register('asset_mapper.public_assets_path_resolver')
            ->setArguments(['/assets/build/']);

        $container
            ->register('asset_mapper.local_public_assets_filesystem')
            ->setArguments([$this->projectDir.'/public']);

        $container
            ->register('asset_mapper.compiled_asset_mapper_config_reader')
            ->setArguments([$this->projectDir.'/public/assets/build']);

        $pass = new AssetMapperWebRootPass();
        $pass->process($container);

        $this->assertSame(
            $this->projectDir.'/docroot',
            $container->findDefinition('asset_mapper.local_public_assets_filesystem')->getArgument(0)
        );
        $this->assertSame(
            $this->projectDir.'/docroot/assets/build/',
            $container->findDefinition('asset_mapper.compiled_asset_mapper_config_reader')->getArgument(0)
        );
    }

    public function testAssetMapperArgumentsAreRebasedToMauticScaffoldWebRoot(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->projectDir);

        file_put_contents($this->projectDir.'/composer.json', json_encode([
            'extra' => [
                'mautic-scaffold' => [
                    'locations' => [
                        'web-root' => 'docroot/',
                    ],
                ],
            ],
        ]));

        $container
            ->register('asset_mapper.public_assets_path_resolver')
            ->setArguments(['/assets/build/']);

        $container
            ->register('asset_mapper.local_public_assets_filesystem')
            ->setArguments([$this->projectDir.'/public']);

        $container
            ->register('asset_mapper.compiled_asset_mapper_config_reader')
            ->setArguments([$this->projectDir.'/public/assets/build']);

        $pass = new AssetMapperWebRootPass();
        $pass->process($container);

        $this->assertSame(
            $this->projectDir.'/docroot',
            $container->findDefinition('asset_mapper.local_public_assets_filesystem')->getArgument(0)
        );
        $this->assertSame(
            $this->projectDir.'/docroot/assets/build/',
            $container->findDefinition('asset_mapper.compiled_asset_mapper_config_reader')->getArgument(0)
        );
    }

    public function testAssetMapperArgumentsFallBackToKernelProjectDirForRootServedInstall(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->projectDir);

        $container
            ->register('asset_mapper.public_assets_path_resolver')
            ->setArguments(['/assets/build']);

        $container
            ->register('asset_mapper.local_public_assets_filesystem')
            ->setArguments([$this->projectDir.'/public']);

        $container
            ->register('asset_mapper.compiled_asset_mapper_config_reader')
            ->setArguments([$this->projectDir.'/public/assets/build']);

        $pass = new AssetMapperWebRootPass();
        $pass->process($container);

        $this->assertSame(
            $this->projectDir,
            $container->findDefinition('asset_mapper.local_public_assets_filesystem')->getArgument(0)
        );
        $this->assertSame(
            $this->projectDir.'/assets/build',
            $container->findDefinition('asset_mapper.compiled_asset_mapper_config_reader')->getArgument(0)
        );
    }
}
