<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\DependencyInjection\Compiler;

use Mautic\CoreBundle\DependencyInjection\Compiler\AssetMapperWebRootPass;
use Mautic\CoreBundle\Loader\ParameterLoader;
use Mautic\CoreBundle\MauticCoreBundle;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AssetMapperWebRootPassTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = realpath(sys_get_temp_dir()).'/mautic_asset_mapper_webroot_'.uniqid();
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

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testUsesRecommendedProjectWebRootExportedByParameterLoader(): void
    {
        $applicationDir = $this->createRecommendedProject();
        $container      = $this->createContainer($applicationDir, '/assets/build/');

        (new AssetMapperWebRootPass())->process($container);

        $this->assertAssetMapperDirectories($container, $applicationDir, $applicationDir.'/assets/build');
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testLocalConfigurationOverridesPathsAndUsesCustomPublicPrefix(): void
    {
        $applicationDir = $this->createRecommendedProject();
        $pathsRoot      = $this->projectDir.'/paths-root';
        $localRoot      = $this->projectDir.'/local-root';
        mkdir($pathsRoot);
        mkdir($localRoot);

        file_put_contents(
            $this->projectDir.'/config/paths_local.php',
            "<?php\n\n\$paths['local_root'] = '%kernel.project_dir%/paths-root';\n"
        );
        file_put_contents(
            $this->projectDir.'/config/local.php',
            "<?php\n\n\$parameters = ['local_root' => ".var_export($localRoot, true)."];\n"
        );

        $container = $this->createContainer($applicationDir, '/custom/build/');

        (new AssetMapperWebRootPass())->process($container);

        $this->assertAssetMapperDirectories($container, $localRoot, $localRoot.'/custom/build');
    }

    public function testPassIsRegisteredBeforeOptimization(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        (new MauticCoreBundle())->build($container);

        $passClasses = static fn (array $passes): array => array_map(
            static fn (CompilerPassInterface $pass): string => $pass::class,
            $passes
        );

        $this->assertContains(
            AssetMapperWebRootPass::class,
            $passClasses($container->getCompilerPassConfig()->getBeforeOptimizationPasses())
        );
        $this->assertNotContains(
            AssetMapperWebRootPass::class,
            $passClasses($container->getCompilerPassConfig()->getBeforeRemovingPasses())
        );
    }

    private function createRecommendedProject(): string
    {
        $applicationDir = $this->projectDir.'/docroot';
        mkdir($applicationDir.'/app/config', 0777, true);
        mkdir($this->projectDir.'/config');

        copy(
            dirname(__DIR__, 6).'/config/paths.php',
            $applicationDir.'/app/config/paths.php'
        );
        file_put_contents($this->projectDir.'/composer.json', json_encode([
            'extra' => [
                'public-dir'      => 'docroot',
                'mautic-scaffold' => [
                    'locations' => [
                        'web-root' => 'docroot/',
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR));
        file_put_contents($this->projectDir.'/config/local.php', "<?php\n\n\$parameters = [];\n");

        return $applicationDir;
    }

    private function createContainer(string $applicationDir, string $publicPrefix): ContainerBuilder
    {
        if (!defined('MAUTIC_ENV')) {
            define('MAUTIC_ENV', 'test');
        }

        putenv('MAUTIC_LOCAL_ROOT');
        unset($_ENV['MAUTIC_LOCAL_ROOT'], $_SERVER['MAUTIC_LOCAL_ROOT']);

        $parameterLoader = new ParameterLoader($applicationDir.'/app');
        $parameterLoader->loadIntoEnvironment();

        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->projectDir);
        $container
            ->register('asset_mapper.public_assets_path_resolver')
            ->setArguments([$publicPrefix]);
        $container
            ->register('asset_mapper.local_public_assets_filesystem')
            ->setArguments([$this->projectDir.'/public']);
        $container
            ->register('asset_mapper.compiled_asset_mapper_config_reader')
            ->setArguments([$this->projectDir.'/public/assets/build']);

        return $container;
    }

    private function assertAssetMapperDirectories(ContainerBuilder $container, string $webRoot, string $compiledAssets): void
    {
        $this->assertSame(
            $webRoot,
            $container->getDefinition('asset_mapper.local_public_assets_filesystem')->getArgument(0)
        );
        $this->assertSame(
            $compiledAssets,
            $container->getDefinition('asset_mapper.compiled_asset_mapper_config_reader')->getArgument(0)
        );
    }
}
