<?php

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Exception\BadConfigurationException;
use Mautic\CoreBundle\Exception\FileExistsException;
use Mautic\CoreBundle\Exception\FileNotFoundException;
use Mautic\CoreBundle\Templating\Helper\ThemeHelper as TemplatingThemeHelper;

interface ThemeHelperInterface
{
    /**
     * Get theme names which are stock Mautic.
     *
     * @return string[]
     */
    public function getDefaultThemes();

    /**
     * @param string $defaultTheme
     *
     * @return void
     */
    public function setDefaultTheme($defaultTheme);

    /**
     * @param string $themeName
     *
     * @return TemplatingThemeHelper
     *
     * @throws BadConfigurationException
     * @throws FileNotFoundException
     */
    public function createThemeHelper($themeName);

    /**
     * @param string $theme
     *
     * @return bool
     */
    public function exists($theme);

    /**
     * @param string      $theme      original theme dir name
     * @param string      $newName
     * @param string|null $newDirName if not set then it will be generated from the $newName param
     *
     * @throws FileExistsException
     * @throws FileNotFoundException
     *
     * @return void
     */
    public function copy($theme, $newName, $newDirName = null);

    /**
     * @param string $theme
     * @param string $newName
     *
     * @throws FileNotFoundException
     * @throws FileExistsException
     *
     * @return void
     */
    public function rename($theme, $newName);

    /**
     * @param string $theme
     *
     * @throws FileNotFoundException
     *
     * @return void
     */
    public function delete($theme);

    /**
     * Fetches the optional settings from the defined steps.
     *
     * @return mixed[]
     */
    public function getOptionalSettings();

    /**
     * @param string $template
     *
     * @return string The logical name for the template
     */
    public function checkForTwigTemplate($template);

    /**
     * @param string $specificFeature
     * @param bool   $extended        returns extended information about the themes
     * @param bool   $ignoreCache     true to get the fresh info
     * @param bool   $includeDirs     true to get the theme dir details
     *
     * @return array<string[]>|string[]
     */
    public function getInstalledThemes($specificFeature = 'all', $extended = false, $ignoreCache = false, $includeDirs = true);

    /**
     * @param string $theme
     * @param bool   $throwException
     *
     * @return TemplatingThemeHelper
     *
     * @throws FileNotFoundException
     * @throws BadConfigurationException
     */
    public function getTheme($theme = 'current', $throwException = false);

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
    public function install($zipFile);

    /**
     * Get the error message from the zip archive.
     *
     * @param \ZipArchive $archive
     *
     * @return string
     */
    public function getExtractError($archive);

    /**
     * Creates a zip file from a theme and returns the path where it's stored.
     *
     * @param string $themeName
     *
     * @return string
     *
     * @throws \Exception
     */
    public function zip($themeName);

    public function getCurrentTheme(string $template, string $specificFeature): string;
}
