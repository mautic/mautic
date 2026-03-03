<?php

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Exception\BadConfigurationException;
use Mautic\CoreBundle\Exception\FileExistsException;
use Mautic\CoreBundle\Exception\FileNotFoundException;
use Mautic\CoreBundle\Twig\Helper\ThemeHelper as twigThemeHelper;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\BuilderIntegrationsHelper;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class ThemeHelper implements ThemeHelperInterface
{
    public const HIDDEN_THEMES_TXT = 'hidden-themes.txt';

    /**
     * @var array<string, mixed[]>
     */
    private array $themes = [];

    /**
     * @var array<string, mixed[]>
     */
    private array $themesInfo = [];

    private array $steps = [];

    /**
     * @var string
     */
    private $defaultTheme;

    /**
     * @var twigThemeHelper[]
     */
    private array $themeHelpers = [];

    private Filesystem $filesystem;

    private Finder $finder;

    private bool $themesLoadedFromFilesystem = false;

    /**
     * Default themes which cannot be deleted.
     *
     * @var string[]
     */
    protected $defaultThemes = [
        '_1-2-1-2-column',
        '_1-2-1-column',
        '_1-2-column',
        '_1-3-1-3-column',
        '_1-3-column',
        '_connect-through-content',
        '_educate',
        '_gallery',
        '_make-announcement',
        '_showcase',
        '_simple-text',
        '_survey',
        '_welcome',
        'attract',
        'aurora',
        'blank',
        'blend',
        'brienz',
        'capture',
        'cards',
        'chord',
        'confirmme',
        'creative',
        'eclipse',
        'formscape',
        'fresh-center',
        'fresh-fixed',
        'fresh-left',
        'fresh-wide',
        'goldstar',
        'mono',
        'neopolitan',
        'oxygen',
        'paprika',
        'reachout',
        'skyline',
        'sparse',
        'sunday',
        'system',
        'trulypersonal',
        'vibrant',
    ];

    /**
     * @var array<int, string>
     */
    private array $hiddenThemes = [];

    public function __construct(
        private PathsHelper $pathsHelper,
        private Environment $twig,
        private TranslatorInterface $translator,
        private CoreParametersHelper $coreParametersHelper,
        Filesystem $filesystem,
        Finder $finder,
        private BuilderIntegrationsHelper $builderIntegrationsHelper,
    ) {
        $this->filesystem                = clone $filesystem;
        $this->finder                    = clone $finder;
    }

    public function getDefaultThemes()
    {
        return $this->defaultThemes;
    }

    /**
     * @param string[] $themes
     */
    public function addDefaultThemes(array $themes): void
    {
        $this->defaultThemes = array_merge($this->defaultThemes, $themes);
    }

    public function setDefaultTheme($defaultTheme): void
    {
        $this->defaultTheme = $defaultTheme;
    }

    public function createThemeHelper($themeName): twigThemeHelper
    {
        if ('current' === $themeName) {
            $themeName = $this->defaultTheme;
        }

        return new twigThemeHelper($this->pathsHelper, $themeName);
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

    public function exists($theme): bool
    {
        $root    = $this->pathsHelper->getSystemPath('themes', true).'/';
        $dirName = $this->getDirectoryName($theme);

        return $this->filesystem->exists($root.$dirName);
    }

    public function copy($theme, $newName, $newDirName = null): void
    {
        $root   = $this->pathsHelper->getSystemPath('themes', true).'/';
        $themes = $this->getInstalledThemes();

        // check to make sure the theme exists
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

    public function rename($theme, $newName): void
    {
        $root   = $this->pathsHelper->getSystemPath('themes', true).'/';
        $themes = $this->getInstalledThemes();

        // check to make sure the theme exists
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

    public function delete($theme): void
    {
        $root   = $this->pathsHelper->getSystemPath('themes', true).'/';
        $themes = $this->getInstalledThemes();

        // check to make sure the theme exists
        if (!isset($themes[$theme])) {
            throw new FileNotFoundException($theme.' not found!');
        }

        if (in_array($theme, $this->getDefaultThemes(), true)) {
            $this->addToHidden($theme);

            return;
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
     * @return mixed[]
     */
    public function getOptionalSettings(): array
    {
        $minors = [];

        foreach ($this->steps as $step) {
            foreach ($step->checkOptionalSettings() as $minor) {
                $minors[] = $minor;
            }
        }

        return $minors;
    }

    public function checkForTwigTemplate($template): string
    {
        if ($this->twig->getLoader()->exists($template)) {
            return $template;
        }

        // Try any theme as a fall back starting with default
        return $this->findThemeWithTemplate($template);
    }

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
                        } catch (FileNotFoundException) {
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

    public function install($zipFile): bool
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
            if (str_starts_with($entry, '/')) {
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
     * @param \ZipArchive::ER_* $archive
     */
    public function getExtractError(int $archive): string
    {
        return match ($archive) {
            \ZipArchive::ER_EXISTS => 'mautic.core.update.archive_file_exists',
            \ZipArchive::ER_INCONS, \ZipArchive::ER_INVAL, \ZipArchive::ER_MEMORY => 'mautic.core.update.archive_zip_corrupt',
            \ZipArchive::ER_NOENT => 'mautic.core.update.archive_no_such_file',
            \ZipArchive::ER_NOZIP => 'mautic.core.update.archive_not_valid_zip',
            default               => 'mautic.core.update.archive_could_not_open',
        };
    }

    public function zip($themeName)
    {
        $themePath = $this->pathsHelper->getSystemPath('themes', true).'/'.$themeName;
        $tmpPath   = $this->pathsHelper->getSystemPath('tmp', true).'/tmp_'.$themeName.'.zip';
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
    }

    /**
     * @throws BadConfigurationException
     */
    private function findThemeWithTemplate(string $template): string
    {
        preg_match('/^@themes\/(.*?)\/(.*?)$/', $template, $match);

        $requestedThemeName = $match[1];
        $templatePath       = $match[2];

        // Try the default theme first
        $defaultTheme = $this->getTheme();

        if ($requestedThemeName !== $defaultTheme->getTheme()) {
            $defaultTemplate = '@themes/'.$defaultTheme->getTheme().'/'.$templatePath;
            if ($this->twig->getLoader()->exists($defaultTemplate)) {
                return $defaultTemplate;
            }
        }

        // Find any theme as a fallback
        $themes = $this->getInstalledThemes('all', true);

        foreach ($themes as $theme) {
            // Already handled the default
            if ($theme['key'] === $defaultTheme->getTheme()) {
                continue;
            }

            $fallbackTemplate = '@themes/'.$theme['key'].'/'.$templatePath;

            if ($this->twig->getLoader()->exists($template)) {
                return $fallbackTemplate;
            }
        }

        throw new BadConfigurationException(sprintf('Could not find theme %s nor a fall back theme to replace it', $requestedThemeName));
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

            // fix for legacy themes who do not have a builder configured
            if (empty($config['builder']) || !is_array($config['builder'])) {
                $config['builder'] = ['legacy'];
            }

            $this->themesInfo[$key][$theme->getBasename()]['config']     = $config;
            $this->themesInfo[$key][$theme->getBasename()]['visibility'] = $this->getVisibility($theme);

            if (empty($this->themesInfo[$key][$theme->getBasename()]['visibility'])) {
                unset($this->themesInfo[$key][$theme->getBasename()]['visibility']);
            }

            if (!$includeDirs) {
                continue;
            }

            $this->themesInfo[$key][$theme->getBasename()]['dir']            = $theme->getRealPath();
            $this->themesInfo[$key][$theme->getBasename()]['themesLocalDir'] = $this->pathsHelper->getSystemPath('themes');
        }

        $this->sortThemesInfo($key);
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
        } catch (IntegrationNotFoundException) {
            // Assume legacy builder
            $builderName = 'legacy';
        }

        $builderRequested = $config['builder'] ?? ['legacy'];

        // is the theme configured to be used with the current builder
        if (!is_array($builderRequested)) {
            throw new BadConfigurationException(sprintf('Theme %s not configured properly: builder property in the config.json', $config['name']));
        }

        return in_array($builderName, $builderRequested);
    }

    public function getCurrentTheme(string $template, string $specificFeature): string
    {
        if ('mautic_code_mode' !== $template && !in_array($template, array_keys($this->getInstalledThemes($specificFeature)))) {
            return $this->coreParametersHelper->get('theme_email_default');
        }

        return $template;
    }

    /**
     * @return array|string[]
     */
    private function getHiddenThemes(): array
    {
        if (count($this->hiddenThemes)) {
            return $this->hiddenThemes;
        }

        if (!$this->filesystem->exists($hidden = $this->pathsHelper->getThemesPath().'/'.self::HIDDEN_THEMES_TXT)) {
            return [];
        }

        return $this->hiddenThemes = array_map(fn ($item) => trim($item), explode('|', $this->filesystem->readFile($hidden)));
    }

    /**
     * @throws IOException
     */
    private function addToHidden(string $theme): void
    {
        $hidden = $this->createHiddenTxtIfNotExists();
        $this->filesystem->appendToFile($hidden, sprintf('|%s', $theme));
    }

    /**
     * @return array<string, bool>
     */
    private function getVisibility(SplFileInfo $theme): array
    {
        $themeName = $theme->getBasename();

        if (!in_array($themeName, $this->defaultThemes, true)) {
            return [];
        }

        return ['hidden' => in_array($themeName, $this->getHiddenThemes(), true)];
    }

    /**
     * @throws IOException
     */
    public function toggleVisibility(string $themeName): void
    {
        if (!in_array($themeName, $this->getDefaultThemes(), true)) {
            return;
        }

        $hidden       = $this->createHiddenTxtIfNotExists();
        $hiddenThemes = array_values(array_filter(array_unique(explode('|', $this->filesystem->readFile($hidden)))));

        if (in_array($themeName, $hiddenThemes, true)) {
            $this->removeFromHidden($themeName, $hiddenThemes);
        } else {
            $this->addToHidden($themeName);
        }
    }

    private function sortThemesInfo(string $key): void
    {
        $hiddenThemes = [];
        $themes       = [];

        foreach ($this->themesInfo[$key] as $data) {
            if (isset($data['visibility']['hidden']) && $data['visibility']['hidden']) {
                $hiddenThemes[$key][$data['key']] = $data;
            } else {
                $themes[$key][$data['key']] = $data;
            }
        }

        $this->themesInfo[$key] = array_merge($themes[$key] ?? [], $hiddenThemes[$key] ?? []);
    }

    private function createHiddenTxtIfNotExists(): string
    {
        if (!$this->filesystem->exists($hidden = $this->pathsHelper->getThemesPath().'/'.self::HIDDEN_THEMES_TXT)) {
            $this->filesystem->touch($hidden);
        }

        return $hidden;
    }

    /**
     * @param string[] $hiddenThemes
     *
     * @throws IOException
     */
    private function removeFromHidden(string $themeName, array $hiddenThemes): void
    {
        $hidden      = $this->createHiddenTxtIfNotExists();
        $keyToRemove = array_search($themeName, $hiddenThemes, true);

        if (false !== $keyToRemove) {
            unset($hiddenThemes[$keyToRemove]);

            if (empty($hiddenThemes)) {
                $this->filesystem->remove($hidden);
            } else {
                $this->filesystem->dumpFile($hidden, sprintf('|%s', implode('|', $hiddenThemes)));
            }
        }
    }
}
