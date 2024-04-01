<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Resource\FileExistenceResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SystemThemeTemplatePathPass implements CompilerPassInterface
{
    /**
     * Processes the container to update Twig template paths.
     *
     * This method updates the Twig template paths to pick up templates from the "<application_dir>/themes/system" directory.
     * It retrieves the definition of the Twig filesystem loader service and modifies it to include the system theme directory.
     * Additionally, it iterates through bundle template paths and adds them to the Twig filesystem loader.
     */
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition('twig.loader.native_filesystem')) {
            // Retrieve the definition of the Twig filesystem loader service.
            $twigFilesystemLoaderDefinition = $container->getDefinition('twig.loader.native_filesystem');

            // Get the application directory from parameters
            $applicationDir = $container->getParameter('mautic.application_dir');

            // Define the system theme directory
            $systemThemeDir = $applicationDir.DIRECTORY_SEPARATOR.'themes/system';

            // If the system theme directory exists, we are registering paths.
            if (file_exists($systemThemeDir)) {
                // Remove paths registered in TwigExtension for re-registration.
                $twigFilesystemLoaderDefinition->removeMethodCall('addPath');

                // Re-register user-configured paths.
                $paths = $container->getDefinition('twig.template_iterator')->getArgument(1);
                foreach ($paths as $path => $namespace) {
                    if (!$namespace) {
                        $twigFilesystemLoaderDefinition->addMethodCall('addPath', [$path]);
                    } else {
                        $twigFilesystemLoaderDefinition->addMethodCall('addPath', [$path, $namespace]);
                    }
                }

                // Re-register twig default_path.
                $defaultTwigPath = $container->getParameterBag()->get('twig.default_path');
                if (file_exists($defaultTwigPath)) {
                    $twigFilesystemLoaderDefinition->addMethodCall('addPath', [$defaultTwigPath]);
                }
                $container->addResource(new FileExistenceResource($defaultTwigPath));

                // Re-register bundle paths adding `themes/system` path.
                foreach ($this->getBundleTemplatePaths($container) as $name => $paths) {
                    $namespace = $this->normalizeBundleName($name);

                    foreach ($paths as $path) {
                        $twigFilesystemLoaderDefinition->addMethodCall('addPath', [$path, $namespace]);
                    }

                    if ($paths) {
                        // the last path must be the bundle views directory
                        $twigFilesystemLoaderDefinition->addMethodCall('addPath', [$path, '!'.$namespace]);
                    }
                }
            }
        }
    }

    /**
     * Get the bundle template paths for registration.
     *
     * @return array<string, array<int, string>>
     */
    private function getBundleTemplatePaths(ContainerBuilder $container): array
    {
        $bundleHierarchy = [];
        $applicationDir  = $container->getParameterBag()->get('mautic.application_dir');
        $systemThemeDir  = $applicationDir.DIRECTORY_SEPARATOR.'themes/system';
        foreach ($container->getParameter('kernel.bundles_metadata') as $name => $bundle) {
            // Default override bundle path.
            $defaultOverrideBundlePath = $container->getParameterBag()->get('twig.default_path').'/bundles/'.$name;

            if (file_exists($defaultOverrideBundlePath)) {
                $bundleHierarchy[$name][] = $defaultOverrideBundlePath;
            }
            $container->addResource(new FileExistenceResource($defaultOverrideBundlePath));

            // The `themes/system` override a path for bundle.
            $bundleName              = pathinfo($bundle['path'])['filename'];
            $themeOverrideBundlePath = $systemThemeDir.DIRECTORY_SEPARATOR.$bundleName;
            if (file_exists($dir = $themeOverrideBundlePath.'/Resources/views')) {
                $bundleHierarchy[$name][] = $dir;
                $container->addResource(new FileExistenceResource($dir));
            }

            if (file_exists($dir = $bundle['path'].'/Resources/views') || file_exists($dir = $bundle['path'].'/templates')) {
                $bundleHierarchy[$name][] = $dir;
            }
            $container->addResource(new FileExistenceResource($dir));
        }

        return $bundleHierarchy;
    }

    private function normalizeBundleName(string $name): string
    {
        if (str_ends_with($name, 'Bundle')) {
            $name = substr($name, 0, -6);
        }

        return $name;
    }
}
