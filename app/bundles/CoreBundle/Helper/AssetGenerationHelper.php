<?php

namespace Mautic\CoreBundle\Helper;

use MatthiasMullie\Minify;
use Symfony\Component\Finder\Finder;

class AssetGenerationHelper
{
    // Temporary array of libraries to load from node_modules before we switch
    // to Symfony Encore. This is the first step to load libraries from NPM.
    private const NODE_MODULES = [
        'mousetrap/mousetrap.js', // Needed for keyboard shortcuts
        'jquery/dist/jquery.js', // Needed for everything. It's the underlying framework.
        'js-cookie/src/js.cookie.js', // Needed for cookies.
        'bootstrap/dist/js/bootstrap.js', // Needed for the UI components like bodal boxes.
        'jquery-form/src/jquery.form.js', // Needed for ajax forms with file attachments.
        'moment/min/moment.min.js', // Needed for date/time formatting.
        'jquery.caret/dist/jquery.caret.js', // Needed for the text editor Twitter-like mentions (tokens).
        'codemirror/lib/codemirror.js', // Needed for the legacy code-mode editor.
        'codemirror/addon/hint/show-hint.js', // Needed for the legacy code-mode editor.
        'codemirror/mode/xml/xml.js', // Needed for the legacy code-mode editor.
        'codemirror/mode/javascript/javascript.js', // Needed for the legacy code-mode editor.
        'codemirror/mode/htmlmixed/htmlmixed.js', // Needed for the legacy code-mode editor.
        'codemirror/mode/css/css.js', // Needed for the legacy code-mode editor.
        'jquery.cookie/jquery.cookie.js', // A simple, lightweight jQuery plugin for reading, writing and deleting cookies.
        'jsplumb/dist/js/jsplumb.js', // Needed for the campaign builder.
        'typeahead.js/dist/typeahead.bundle.js', // Needed for the Twitter-like mentions (tokens).
        'jquery-datetimepicker/build/jquery.datetimepicker.full.js', // Needed for the date/time UI selector.
        'shufflejs/dist/shuffle.js', // Needed for the plugin list page.
        '@claviska/jquery-minicolors/jquery.minicolors.js', // Needed for the color picker.
        'dropzone/dist/dropzone.js', // Needed for the file upload in the asset detail page.
        'multiselect/js/jquery.multi-select.js', // Needed for the multiselect UI component.
        'chart.js/dist/Chart.js', // Needed for the charts.
        'chosen-js/chosen.jquery.js',
        'at.js/dist/js/jquery.atwho.js',
        'jvectormap-next/jquery-jvectormap.js',
        'modernizr/modernizr-mautic-dist.js',
        'jquery.quicksearch/src/jquery.quicksearch.js',
        'jquery-ui/ui/version.js',
        'jquery-ui/ui/widget.js',
        'jquery-ui/ui/plugin.js',
        'jquery-ui/ui/position.js',
        'jquery-ui/ui/data.js',
        'jquery-ui/ui/disable-selection.js',
        'jquery-ui/ui/focusable.js',
        'jquery-ui/ui/form-reset-mixin.js',
        'jquery-ui/ui/jquery-patch.js',
        'jquery-ui/ui/keycode.js',
        'jquery-ui/ui/labels.js',
        'jquery-ui/ui/scroll-parent.js',
        'jquery-ui/ui/tabbable.js',
        'jquery-ui/ui/unique-id.js',
        'jquery-ui/ui/effect.js',
        'jquery-ui/ui/safe-blur.js', // needed for the legacy builder
        'jquery-ui/ui/widgets/mouse.js',
        'jquery-ui/ui/widgets/draggable.js',
        'jquery-ui/ui/widgets/droppable.js',
        'jquery-ui/ui/widgets/selectable.js',
        'jquery-ui/ui/widgets/sortable.js',
        'jquery-ui/ui/vendor/jquery-color/jquery.color.js',
        'jquery-ui/ui/effects/effect-drop.js',
        'jquery-ui/ui/effects/effect-fade.js',
        'jquery-ui/ui/effects/effect-size.js',
        'jquery-ui/ui/effects/effect-slide.js',
        'jquery-ui/ui/effects/effect-transfer.js',
        'jquery-ui/ui/safe-active-element.js', // needed for ElFinder
        'jquery-ui/ui/widgets/button.js', // needed for ElFinder
        'jquery-ui/ui/widgets/resizable.js', // needed for ElFinder
        'jquery-ui/ui/widgets/slider.js', // needed for ElFinder
        'jquery-ui/ui/widgets/controlgroup.js', // needed for ElFinder
        'jquery-ui-touch-punch/jquery.ui.touch-punch.js', // Needed for touch devices, and needs to be added after the jquery-ui components
    ];

    private string $version;

    public function __construct(
        private BundleHelper $bundleHelper,
        private PathsHelper $pathsHelper,
        CoreParametersHelper $coreParametersHelper,
        AppVersion $appVersion
    ) {
        $this->version = substr(hash('sha1', $coreParametersHelper->get('secret_key').$appVersion->getVersion()), 0, 8);
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
            $env        = ($forceRegeneration) ? 'prod' : MAUTIC_ENV;
            $rootPath   = $this->pathsHelper->getSystemPath('assets_root');
            $assetsPath = $this->pathsHelper->getSystemPath('media');

            $assetsFullPath = "$rootPath/$assetsPath";
            if ('prod' == $env) {
                $loadAll = false; // by default, loading should not be required

                // check for libraries and app files and generate them if they don't exist if in prod environment
                $prodFiles = [
                    'css/libraries.css',
                    'css/app.css',
                    'js/libraries.js',
                    'js/app.js',
                ];

                foreach ($prodFiles as $file) {
                    if (!file_exists("$assetsFullPath/$file")) {
                        $loadAll = true; // it's missing so compile it
                        break;
                    }
                }
            }

            if ($loadAll || $forceRegeneration) {
                if ('prod' == $env) {
                    ini_set('max_execution_time', '300');

                    $inProgressFile = "$assetsFullPath/generation_in_progress.txt";

                    if (!$forceRegeneration) {
                        while (file_exists($inProgressFile)) {
                            // dummy loop to prevent conflicts if one process is actively regenerating assets
                        }
                    }
                    file_put_contents($inProgressFile, date('r'));
                }

                foreach (self::NODE_MODULES as $path) {
                    $relPath  = "node_modules/{$path}";
                    $fullPath = "{$this->pathsHelper->getVendorRootPath()}/{$relPath}";
                    $ext      = pathinfo($relPath, PATHINFO_EXTENSION);
                    $details  = [
                        'fullPath' => $fullPath,
                        'relPath'  => $relPath,
                    ];

                    if ('prod' == $env) {
                        $assets[$ext]['libraries'][$relPath] = $details;
                    } else {
                        $assets[$ext][$relPath] = $details;
                    }
                }

                $modifiedLast = [];

                // get a list of all core asset files
                $bundles = $this->bundleHelper->getMauticBundles();

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

                // combine the files into their corresponding name and put in the root media folder
                if ('prod' == $env) {
                    $checkPaths = [
                        $assetsFullPath,
                        "$assetsFullPath/css",
                        "$assetsFullPath/js",
                    ];
                    array_walk($checkPaths, function ($path): void {
                        if (!file_exists($path)) {
                            mkdir($path);
                        }
                    });

                    foreach ($assets as $type => $groups) {
                        foreach ($groups as $group => $files) {
                            $assetFile = "$assetsFullPath/$type/$group.$type";

                            // only refresh if a change has occurred
                            $modified = ($forceRegeneration || !file_exists($assetFile)) ? true : filemtime($assetFile) < $modifiedLast[$type][$group];

                            if ($modified) {
                                if (file_exists($assetFile)) {
                                    // delete it
                                    unlink($assetFile);
                                }

                                if ('css' == $type) {
                                    $minifier = new Minify\CSS(...array_column($files, 'fullPath'));
                                    $minifier->minify($assetFile);
                                } else {
                                    $minifier = new Minify\JS(...array_column($files, 'fullPath'));
                                    $minifier->minify($assetFile);
                                }
                            }
                        }
                    }

                    unlink($inProgressFile);
                }
            }

            if ('prod' == $env) {
                // return prod generated assets
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
                foreach ($assets as &$typeAssets) {
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
     */
    protected function findAssets($dir, $ext, $env, &$assets): array
    {
        $rootPath    = str_replace('\\', '/', $this->pathsHelper->getSystemPath('assets_root').'/');
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

                $sort = fn (\SplFileInfo $a, \SplFileInfo $b): int => strnatcmp($a->getRealpath(), $b->getRealpath());
                $files->sort($sort);

                foreach ($files as $file) {
                    $fullPath = $file->getPathname();
                    $relPath  = str_replace($rootPath, '', $file->getPathname());
                    if (str_starts_with($relPath, '/')) {
                        $relPath = substr($relPath, 1);
                    }

                    $details = [
                        'fullPath' => $fullPath,
                        'relPath'  => $relPath,
                    ];

                    if ('prod' == $env) {
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

        $sort = fn (\SplFileInfo $a, \SplFileInfo $b): int => strnatcmp($a->getRealpath(), $b->getRealpath());
        $files->sort($sort);

        foreach ($files as $file) {
            $fullPath = str_replace('\\', '/', $file->getPathname());
            $relPath  = str_replace($rootPath, '', $fullPath);

            $details = [
                'fullPath' => $fullPath,
                'relPath'  => $relPath,
            ];

            if ('prod' == $env) {
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
     */
    protected function findOverrides($env, &$assets): array
    {
        $rootPath      = $this->pathsHelper->getSystemPath('assets_root');
        $currentTheme  = $this->pathsHelper->getSystemPath('current_theme');
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

                    if ('prod' == $env) {
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
