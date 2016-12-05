<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Finder\Finder;

/**
 * Class AssetGenerationHelper.
 */
class AssetGenerationHelper
{
    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * @var
     */
    private $version;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
        $this->version = substr(hash('sha1', $this->factory->getParameter('secret_key').$this->factory->getVersion()), 0, 8);
    }

    /**
     * Generates and returns assets.
     *
     * @param bool $forceRegeneration
     *
     * @return array
     */
    public function getAssets($forceRegeneration = false)
    {
        static $assets = [];

        if (empty($assets)) {
            $loadAll    = true;
            $env        = ($forceRegeneration) ? 'prod' : $this->factory->getEnvironment();
            $rootPath   = $this->factory->getSystemPath('assets_root');
            $assetsPath = $this->factory->getSystemPath('assets');

            $assetsFullPath = "$rootPath/$assetsPath";
            if ($env == 'prod') {
                $loadAll = false; //by default, loading should not be required

                //check for libraries and app files and generate them if they don't exist if in prod environment
                $prodFiles = [
                    'css/libraries.css',
                    'css/app.css',
                    'js/libraries.js',
                    'js/app.js',
                ];

                foreach ($prodFiles as $file) {
                    if (!file_exists("$assetsFullPath/$file")) {
                        $loadAll = true; //it's missing so compile it
                        break;
                    }
                }
            }

            if ($loadAll || $forceRegeneration) {
                if ($env == 'prod') {
                    ini_set('max_execution_time', 300);

                    $inProgressFile = "$assetsFullPath/generation_in_progress.txt";

                    if (!$forceRegeneration) {
                        while (file_exists($inProgressFile)) {
                            //dummy loop to prevent conflicts if one process is actively regenerating assets
                        }
                    }
                    file_put_contents($inProgressFile, date('r'));
                }

                $modifiedLast = [];

                //get a list of all core asset files
                $bundles = $this->factory->getParameter('bundles');

                $fileTypes = ['css', 'js'];
                foreach ($bundles as $bundle) {
                    foreach ($fileTypes as $ft) {
                        if (!isset($modifiedLast[$ft])) {
                            $modifiedLast[$ft] = [];
                        }
                        $dir = "{$bundle['directory']}/Assets/$ft";
                        if (file_exists($dir)) {
                            $modifiedLast[$ft] = array_merge($modifiedLast[$ft], $this->findAssets($dir, $ft, $env, $assets));
                        }
                    }
                }
                $modifiedLast = array_merge($modifiedLast, $this->findOverrides($env, $assets));

                //combine the files into their corresponding name and put in the root media folder
                if ($env == 'prod') {
                    $checkPaths = [
                        $assetsFullPath,
                        "$assetsFullPath/css",
                        "$assetsFullPath/js",
                    ];
                    array_walk($checkPaths, function ($path) {
                        if (!file_exists($path)) {
                            mkdir($path);
                        }
                    });

                    $useMinify = class_exists('\Minify');

                    foreach ($assets as $type => $groups) {
                        foreach ($groups as $group => $files) {
                            $assetFile = "$assetsFullPath/$type/$group.$type";

                            //only refresh if a change has occurred
                            $modified = ($forceRegeneration || !file_exists($assetFile)) ? true : filemtime($assetFile) < $modifiedLast[$type][$group];

                            if ($modified) {
                                if (file_exists($assetFile)) {
                                    //delete it
                                    unlink($assetFile);
                                }

                                if ($type == 'css') {
                                    $out = fopen($assetFile, 'w');

                                    foreach ($files as $relPath => $details) {
                                        $cssRel = '../../'.dirname($relPath).'/';
                                        if ($useMinify) {
                                            $content = \Minify::combine([$details['fullPath']], [
                                                'rewriteCssUris'  => false,
                                                'minifierOptions' => [
                                                    'text/css' => [
                                                        'currentDir'          => '',
                                                        'prependRelativePath' => $cssRel,
                                                    ],
                                                ],
                                            ]);
                                        } else {
                                            $content = file_get_contents($details['fullPath']);
                                            $search  = '#url\((?!\s*([\'"]?(((?:https?:)?//)|(?:data\:?:))))\s*([\'"])?#';
                                            $replace = "url($4{$cssRel}";
                                            $content = preg_replace($search, $replace, $content);
                                        }

                                        fwrite($out, $content);
                                    }

                                    fclose($out);
                                } else {
                                    array_walk($files, function (&$file) {
                                        $file = $file['fullPath'];
                                    });
                                    file_put_contents($assetFile, \Minify::combine($files));
                                }
                            }
                        }
                    }

                    unlink($inProgressFile);
                }
            }

            if ($env == 'prod') {
                //return prod generated assets
                $assets = [
                    'css' => [
                        "{$assetsPath}/css/libraries.css?v{$this->version}",
                        "{$assetsPath}/css/app.css?v{$this->version}",
                    ],
                    'js' => [
                        "{$assetsPath}/js/libraries.js?v{$this->version}",
                        "{$assetsPath}/js/app.js?v{$this->version}",
                    ],
                ];
            } else {
                foreach ($assets as $type => &$typeAssets) {
                    $typeAssets = array_keys($typeAssets);
                }
            }
        }

        return $assets;
    }

    /**
     * Finds directory assets.
     *
     * @param string $dir
     * @param string $ext
     * @param string $env
     * @param array  $assets
     *
     * @return array
     */
    protected function findAssets($dir, $ext, $env, &$assets)
    {
        $rootPath    = str_replace('\\', '/', $this->factory->getSystemPath('assets_root').'/');
        $directories = new Finder();
        $directories->directories()->exclude('*less')->depth('0')->ignoreDotFiles(true)->in($dir);

        $modifiedLast = [];

        if (count($directories)) {
            foreach ($directories as $directory) {
                $group = $directory->getBasename();

                // Only auto load directories app or libraries
                if (!in_array($group, ['app', 'libraries'])) {
                    continue;
                }

                $files         = new Finder();
                $thisDirectory = str_replace('\\', '/', $directory->getRealPath());
                $files->files()->depth('0')->name('*.'.$ext)->in($thisDirectory);

                $sort = function (\SplFileInfo $a, \SplFileInfo $b) {
                    return strnatcmp($a->getRealpath(), $b->getRealpath());
                };
                $files->sort($sort);

                foreach ($files as $file) {
                    $fullPath = $file->getPathname();
                    $relPath  = str_replace($rootPath, '', $file->getPathname());
                    if (strpos($relPath, '/') === 0) {
                        $relPath = substr($relPath, 1);
                    }

                    $details = [
                        'fullPath' => $fullPath,
                        'relPath'  => $relPath,
                    ];

                    if ($env == 'prod') {
                        $lastModified = filemtime($fullPath);
                        if (!isset($modifiedLast[$group]) || $lastModified > $modifiedLast[$group]) {
                            $modifiedLast[$group] = $lastModified;
                        }
                        $assets[$ext][$group][$relPath] = $details;
                    } else {
                        $assets[$ext][$relPath] = $details;
                    }
                }
                unset($files);
            }
        }

        unset($directories);
        $files = new Finder();
        $files->files()->depth('0')->ignoreDotFiles(true)->name('*.'.$ext)->in($dir);

        $sort = function (\SplFileInfo $a, \SplFileInfo $b) {
            return strnatcmp($a->getRealpath(), $b->getRealpath());
        };
        $files->sort($sort);

        foreach ($files as $file) {
            $fullPath = str_replace('\\', '/', $file->getPathname());
            $relPath  = str_replace($rootPath, '', $fullPath);

            $details = [
                'fullPath' => $fullPath,
                'relPath'  => $relPath,
            ];

            if ($env == 'prod') {
                $lastModified = filemtime($fullPath);
                if (!isset($modifiedLast['app']) || $lastModified > $modifiedLast['app']) {
                    $modifiedLast['app'] = $lastModified;
                }
                $assets[$ext]['app'][$relPath] = $details;
            } else {
                $assets[$ext][$relPath] = $details;
            }
        }
        unset($files);

        return $modifiedLast;
    }

    /**
     * Find asset overrides in the template.
     *
     * @param $env
     * @param $assets
     *
     * @return array
     */
    protected function findOverrides($env, &$assets)
    {
        $rootPath      = $this->factory->getSystemPath('assets_root');
        $currentTheme  = $this->factory->getSystemPath('current_theme');
        $modifiedLast  = [];
        $types         = ['css', 'js'];
        $overrideFiles = [
            'libraries' => 'libraries_custom',
            'app'       => 'app_custom',
        ];

        foreach ($types as $ext) {
            foreach ($overrideFiles as $group => $of) {
                if (file_exists("$rootPath/$currentTheme/$ext/$of.$ext")) {
                    $fullPath = "$rootPath/$currentTheme/$ext/$of.$ext";
                    $relPath  = "$currentTheme/$ext/$of.$ext";

                    $details = [
                        'fullPath' => $fullPath,
                        'relPath'  => $relPath,
                    ];

                    if ($env == 'prod') {
                        $lastModified = filemtime($fullPath);
                        if (!isset($modifiedLast[$ext][$group]) || $lastModified > $modifiedLast[$ext][$group]) {
                            $modifiedLast[$ext][$group] = $lastModified;
                        }
                        $assets[$ext][$group][$relPath] = $details;
                    } else {
                        $assets[$ext][$relPath] = $details;
                    }
                }
            }
        }

        return $modifiedLast;
    }
}
