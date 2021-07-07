<?php

namespace Mautic\CoreBundle\Helper;

interface ThemeHelperInterface
{
    public function getDefaultThemes();

    public function setDefaultTheme($defaultTheme);

    public function createThemeHelper($themeName);

    public function exists($theme);

    public function copy($theme, $newName, $newDirName = null);

    public function rename($theme, $newName);

    public function delete($theme);

    public function getOptionalSettings();

    public function checkForTwigTemplate($template);

    public function getInstalledThemes($specificFeature = 'all', $extended = false, $ignoreCache = false, $includeDirs = true);

    public function getTheme($theme = 'current', $throwException = false);

    public function install($zipFile);

    public function getExtractError($archive);

    public function zip($themeName);

    public function getCurrentTheme(string $template, string $specificFeature): string;
}
