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

use Mautic\CoreBundle\Exception\BadConfigurationException;
use Mautic\CoreBundle\Exception\FileExistsException;
use Mautic\CoreBundle\Exception\FileNotFoundException;
use Mautic\CoreBundle\Templating\Helper\ThemeHelper as TemplatingThemeHelper;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\BuilderIntegrationsHelper;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Templating\TemplateReference;
use Symfony\Component\Translation\TranslatorInterface;

class ThemeHelper
{
    /**
     * @var PathsHelper
     */
    private $pathsHelper;

    /**
     * @var TemplatingHelper
     */
    private $templatingHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array|mixed
     */
    private $themes = [];

    /**
     * @var array
     */
    private $themesInfo = [];

    /**
     * @var array
     */
    private $steps = [];

    /**
     * @var string
     */
    private $defaultTheme;

    /**
     * @var TemplatingThemeHelper[]
     */
    private $themeHelpers = [];

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var BuilderIntegrationsHelper
     */
    private $builderIntegrationsHelper;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Finder
     */
    private $finder;

    /**
     * @var bool
     */
    private $themesLoadedFromFilesystem = false;

    /**
     * Default themes which cannot be deleted.
     *
     * @var array
     */
    protected $defaultThemes = [
        'aurora',
        'blank',
        'cards',
        'fresh-center',
        'fresh-fixed',
        'fresh-left',
        'fresh-wide',
        'goldstar',
        'neopolitan',
        'oxygen',
        'skyline',
        'sparse',
        'sunday',
        'system',
        'vibrant',
    ];

    public function __construct(
        PathsHelper $pathsHelper,
        TemplatingHelper $templatingHelper,
        TranslatorInterface $translator,
        CoreParametersHelper $coreParametersHelper,
        Filesystem $filesystem,
        Finder $finder,
        BuilderIntegrationsHelper $builderIntegrationsHelper
    ) {
        $this->pathsHelper               = $pathsHelper;
        $this->templatingHelper          = $templatingHelper;
        $this->translator                = $translator;
        $this->coreParametersHelper      = $coreParametersHelper;
        $this->builderIntegrationsHelper = $builderIntegrationsHelper;
        $this->filesystem                = clone $filesystem;
        $this->finder                    = clone $finder;
    }

    /**
     * Get theme names which are stock Mautic.
     *
     * @return array
     */
    public function getDefaultThemes()
    {
        return $this->defaultThemes;
    }

    /**
     * @param string $defaultTheme
     */
    public function setDefaultTheme($defaultTheme)
    {
        $this->defaultTheme = $defaultTheme;
    }

    /**
     * @param string $themeName
     *
     * @return TemplatingThemeHelper
     *
     * @throws BadConfigurationException
     * @throws FileNotFoundException
     */
    public function createThemeHelper($themeName)
    {
        if ('current' === $themeName) {
            $themeName = $this->defaultTheme;
        }

        return new TemplatingThemeHelper($this->pathsHelper, $themeName);
    }

    /**
     * @param string $newName
     *
     * @return string
     */
    private function getDirectoryName($newName)
    {
        return InputHelper::filename(str_replace(' ', '-', $newName));
    }

    /**
     * @param string $theme
     *
     * @return bool
     */
    public function exists($theme)
    {
        $root    = $this->pathsHelper->getSystemPath('themes', true).'/';
        $dirName = $this->getDirectoryName($theme);

        return $this->filesystem->exists($root.$dirName);
    }

    /**
     * @param string      $theme      original theme dir name
     * @param string      $newName
     * @param string|null $newDirName if not set then it will be generated from the $newName param
     *
     * @throws FileExistsException
     * @throws FileNotFoundException
     */
    public function copy($theme, $newName, $newDirName = null)
    {
        $root   = $this->pathsHelper->getSystemPath('themes', true).'/';
        $themes = $this->getInstalledThemes();

        //check to make sure the theme exists
        if (!isset($themes[$theme])) {
            throw new FileNotFoundException($theme.' not found!');
        }

        $dirName = $this->getDirectoryName($newDirName ?? $newName);

        if ($this->filesystem->exists($root.$dirName)) {
            throw new FileExistsException("$dirName already exists");
        }

        $this->filesystem->mirror($root.$theme, $root.$dirName);

        $this->updateConfig($root.$dirName, $newName);
    }

    /**
     * @param string $theme
     * @param string $newName
     *
     * @throws FileNotFoundException
     * @throws FileExistsException
     */
    public function rename($theme, $newName)
    {
        $root   = $this->pathsHelper->getSystemPath('themes', true).'/';
        $themes = $this->getInstalledThemes();

        //check to make sure the theme exists
        if (!isset($themes[$theme])) {
            throw new FileNotFoundException($theme.' not found!');
        }

        $dirName = $this->getDirectoryName($newName);

        if ($this->filesystem->exists($root.$dirName)) {
            throw new FileExistsException("$dirName already exists");
        }

        $this->filesystem->rename($root.$theme, $root.$dirName);

        $this->updateConfig($root.$theme, $dirName);
    }

    /**
     * @param string $theme
     *
     * @throws FileNotFoundException
     */
    public function delete($theme)
    {
        $root   = $this->pathsHelper->getSystemPath('themes', true).'/';
        $themes = $this->getInstalledThemes();

        //check to make sure the theme exists
        if (!isset($themes[$theme])) {
            throw new FileNotFoundException($theme.' not found!');
        }

        $this->filesystem->remove($root.$theme);
    }

    /**
     * Updates the theme configuration and converts
     * it to json if still using php array.
     */
    private function updateConfig(string $themePath, string $newName): void
    {
        $configJsonPath = "{$themePath}/config.json";

        if ($this->filesystem->exists($configJsonPath)) {
            $config = json_decode($this->filesystem->readFile($configJsonPath), true);
        } else {
            throw new FileNotFoundException("File {$configJsonPath} was not found and so the theme config cannot be updated with new name of {$newName}");
        }

        $config['name'] = $newName;

        $this->filesystem->dumpFile($configJsonPath, json_encode($config));
    }

    /**
     * Fetches the optional settings from the defined steps.
     *
     * @return array
     */
    public function getOptionalSettings()
    {
        $minors = [];

        foreach ($this->steps as $step) {
            foreach ($step->checkOptionalSettings() as $minor) {
                $minors[] = $minor;
            }
        }

        return $minors;
    }

    /**
     * @param string $template
     *
     * @return string The logical name for the template
     */
    public function checkForTwigTemplate($template)
    {
        $parser     = $this->templatingHelper->getTemplateNameParser();
        $templating = $this->templatingHelper->getTemplating();

        $template = $parser->parse($template);

        $twigTemplate = clone $template;
        $twigTemplate->set('engine', 'twig');

        // Does a twig version exist?
        if ($templating->exists($twigTemplate)) {
            return $twigTemplate->getLogicalName();
        }

        // Does a PHP version exist?
        if ($templating->exists($template)) {
            return $template->getLogicalName();
        }

        // Try any theme as a fall back starting with default
        $this->findThemeWithTemplate($templating, $twigTemplate);

        return $twigTemplate->getLogicalName();
    }

    /**
     * @param string $specificFeature
     * @param bool   $extended        returns extended information about the themes
     * @param bool   $ignoreCache     true to get the fresh info
     * @param bool   $includeDirs     true to get the theme dir details
     *
     * @return mixed
     */
    public function getInstalledThemes($specificFeature = 'all', $extended = false, $ignoreCache = false, $includeDirs = true)
    {
        // Use a concatenated key since $includeDirs changes what's returned ($includeDirs used by API controller to prevent from exposing file paths)
        $key = $specificFeature.(int) $includeDirs;
        if (empty($this->themes[$key]) || $ignoreCache) {
            $this->loadThemes($specificFeature, $includeDirs, $key);
        }

        if ($extended) {
            return $this->themesInfo[$key];
        }

        return $this->themes[$key];
    }

    /**
     * @param string $theme
     * @param bool   $throwException
     *
     * @return TemplatingThemeHelper
     *
     * @throws FileNotFoundException
     * @throws BadConfigurationException
     */
    public function getTheme($theme = 'current', $throwException = false)
    {
        if (empty($this->themeHelpers[$theme])) {
            try {
                $this->themeHelpers[$theme] = $this->createThemeHelper($theme);
            } catch (FileNotFoundException $e) {
                if (!$throwException) {
                    // theme wasn't found so just use the first available
                    $themes = $this->getInstalledThemes();

                    foreach ($themes as $installedTheme => $name) {
                        try {
                            if (isset($this->themeHelpers[$installedTheme])) {
                                // theme found so return it
                                return $this->themeHelpers[$installedTheme];
                            } else {
                                $this->themeHelpers[$installedTheme] = $this->createThemeHelper($installedTheme);
                                // found so use this theme
                                $theme = $installedTheme;
                                $found = true;
                                break;
                            }
                        } catch (FileNotFoundException $e) {
                            continue;
                        }
                    }
                }

                if (empty($found)) {
                    // if we get to this point then no template was found so throw an exception regardless
                    throw $e;
                }
            }
        }

        return $this->themeHelpers[$theme];
    }

    /**
     * Install a theme from a zip package.
     *
     * @param string $zipFile path
     *
     * @return bool
     *
     * @throws FileNotFoundException
     * @throws \Exception
     */
    public function install($zipFile)
    {
        if (false === $this->filesystem->exists($zipFile)) {
            throw new FileNotFoundException();
        }

        if (false === class_exists('ZipArchive')) {
            throw new \Exception('mautic.core.ziparchive.not.installed');
        }

        $themeName = basename($zipFile, '.zip');

        if (in_array($themeName, $this->getDefaultThemes())) {
            throw new \Exception($this->translator->trans('mautic.core.theme.default.cannot.overwrite', ['%name%' => $themeName], 'validators'));
        }

        $themePath = $this->pathsHelper->getSystemPath('themes', true).'/'.$themeName;
        $zipper    = new \ZipArchive();
        $archive   = $zipper->open($zipFile);

        if (true !== $archive) {
            throw new \Exception($this->getExtractError($archive));
        }

        $requiredFiles      = ['config.json', 'html/message.html.twig'];
        $foundRequiredFiles = [];
        $allowedFiles       = [];
        $allowedExtensions  = $this->coreParametersHelper->get('theme_import_allowed_extensions');

        $config = [];
        for ($i = 0; $i < $zipper->numFiles; ++$i) {
            $entry = $zipper->getNameIndex($i);
            if (0 === strpos($entry, '/')) {
                $entry = substr($entry, 1);
            }

            $extension = pathinfo($entry, PATHINFO_EXTENSION);

            // Check for required files
            if (in_array($entry, $requiredFiles)) {
                $foundRequiredFiles[] = $entry;
            }

            // Filter out dangerous files like .php
            if (empty($extension) || in_array(strtolower($extension), $allowedExtensions)) {
                $allowedFiles[] = $entry;
            }

            if ('config.json' === $entry) {
                $config = json_decode($zipper->getFromName($entry), true);
            }
        }

        if (!empty($config['features'])) {
            foreach ($config['features'] as $feature) {
                $featureFile     = sprintf('html/%s.html.twig', strtolower($feature));
                $requiredFiles[] = $featureFile;

                if (in_array($featureFile, $allowedFiles)) {
                    $foundRequiredFiles[] = $featureFile;
                }
            }
        }

        if ($missingFiles = array_diff($requiredFiles, $foundRequiredFiles)) {
            throw new FileNotFoundException($this->translator->trans('mautic.core.theme.missing.files', ['%files%' => implode(', ', $missingFiles)], 'validators'));
        }

        // Extract the archive file now
        if (!$zipper->extractTo($themePath, $allowedFiles)) {
            throw new \Exception('mautic.core.update.error_extracting_package');
        } else {
            $zipper->close();
            unlink($zipFile);

            return true;
        }
    }

    /**
     * Get the error message from the zip archive.
     *
     * @param \ZipArchive $archive
     *
     * @return string
     */
    public function getExtractError($archive)
    {
        switch ($archive) {
            case \ZipArchive::ER_EXISTS:
                $error = 'mautic.core.update.archive_file_exists';
                break;
            case \ZipArchive::ER_INCONS:
            case \ZipArchive::ER_INVAL:
            case \ZipArchive::ER_MEMORY:
                $error = 'mautic.core.update.archive_zip_corrupt';
                break;
            case \ZipArchive::ER_NOENT:
                $error = 'mautic.core.update.archive_no_such_file';
                break;
            case \ZipArchive::ER_NOZIP:
                $error = 'mautic.core.update.archive_not_valid_zip';
                break;
            case \ZipArchive::ER_READ:
            case \ZipArchive::ER_SEEK:
            case \ZipArchive::ER_OPEN:
            default:
                $error = 'mautic.core.update.archive_could_not_open';
                break;
        }

        return $error;
    }

    /**
     * Creates a zip file from a theme and returns the path where it's stored.
     *
     * @param string $themeName
     *
     * @return string
     *
     * @throws \Exception
     */
    public function zip($themeName)
    {
        $themePath = $this->pathsHelper->getSystemPath('themes', true).'/'.$themeName;
        $tmpPath   = $this->pathsHelper->getSystemPath('cache', true).'/tmp_'.$themeName.'.zip';
        $zipper    = new \ZipArchive();

        if ($this->filesystem->exists($tmpPath)) {
            $this->filesystem->remove($tmpPath);
        }

        $archive = $zipper->open($tmpPath, \ZipArchive::CREATE);

        $this->finder->files()->in($themePath);

        if (true !== $archive) {
            throw new \Exception($this->getExtractError($archive));
        } else {
            foreach ($this->finder as $file) {
                $filePath  = $file->getRealPath();
                $localPath = $file->getRelativePathname();
                $zipper->addFile($filePath, $localPath);
            }
            $zipper->close();

            return $tmpPath;
        }

        return false;
    }

    /**
     * @throws BadConfigurationException
     * @throws FileNotFoundException
     */
    private function findThemeWithTemplate(EngineInterface $templating, TemplateReference $template)
    {
        preg_match('/^:(.*?):(.*?)$/', $template->getLogicalName(), $match);
        $requestedThemeName = $match[1];

        // Try the default theme first
        $defaultTheme = $this->getTheme();
        if ($requestedThemeName !== $defaultTheme->getTheme()) {
            $template->set('controller', $defaultTheme->getTheme());
            if ($templating->exists($template)) {
                return;
            }
        }

        // Find any theme as a fallback
        $themes = $this->getInstalledThemes('all', true);
        foreach ($themes as $theme) {
            // Already handled the default
            if ($theme['key'] === $defaultTheme->getTheme()) {
                continue;
            }

            // Theme name is stored in the controller parameter
            $template->set('controller', $theme['key']);

            if ($templating->exists($template)) {
                return;
            }
        }
    }

    private function loadThemes(string $specificFeature, bool $includeDirs, string $key): void
    {
        if (!$this->themesLoadedFromFilesystem) {
            $this->themesLoadedFromFilesystem = true;
            // prevent the finder from duplicating directories in its internal state
            // https://symfony.com/doc/current/components/finder.html#usage
            $dir = $this->pathsHelper->getSystemPath('themes', true);
            $this->finder->directories()->depth('0')->ignoreDotFiles(true)->in($dir)->sortByName();
        }

        $this->themes[$key]     = [];
        $this->themesInfo[$key] = [];

        foreach ($this->finder as $theme) {
            if (!$this->filesystem->exists($theme->getRealPath().'/config.json')) {
                continue;
            }

            $config = json_decode($this->filesystem->readFile($theme->getRealPath().'/config.json'), true);

            if (!$this->shouldLoadTheme($config, $specificFeature)) {
                continue;
            }

            $this->themes[$key][$theme->getBasename()] = $config['name'];

            $this->themesInfo[$key][$theme->getBasename()]           = [];
            $this->themesInfo[$key][$theme->getBasename()]['name']   = $config['name'];
            $this->themesInfo[$key][$theme->getBasename()]['key']    = $theme->getBasename();
            $this->themesInfo[$key][$theme->getBasename()]['config'] = $config;

            if (!$includeDirs) {
                continue;
            }

            $this->themesInfo[$key][$theme->getBasename()]['dir']            = $theme->getRealPath();
            $this->themesInfo[$key][$theme->getBasename()]['themesLocalDir'] = $this->pathsHelper->getSystemPath('themes');
        }
    }

    private function shouldLoadTheme(array $config, string $featureRequested): bool
    {
        if ('all' === $featureRequested) {
            return true;
        }

        if (!isset($config['features'])) {
            return false;
        }

        if (!in_array($featureRequested, $config['features'])) {
            return false;
        }

        try {
            $builder     = $this->builderIntegrationsHelper->getBuilder($featureRequested);
            $builderName = $builder->getName();
        } catch (IntegrationNotFoundException $exception) {
            // Assume legacy builder
            $builderName = 'legacy';
        }

        $builderRequested = $config['builder'] ?? 'legacy';

        return $builderName === $builderRequested;
    }
}
