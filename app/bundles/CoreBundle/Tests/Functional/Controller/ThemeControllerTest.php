<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Helper\Filesystem;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;

final class ThemeControllerTest extends MauticMysqlTestCase
{
    private PathsHelper $pathsHelper;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->configParams['legacy_builder_enabled'] = true;
        parent::setUp();

        $this->pathsHelper = $this->getContainer()->get(PathsHelper::class);
        $this->assertInstanceOf(PathsHelper::class, $this->pathsHelper);

        $this->filesystem  = $this->getContainer()->get(Filesystem::class);
        $this->assertInstanceOf(Filesystem::class, $this->filesystem);

        $themePath = $this->pathsHelper->getThemesPath();

        if ($this->filesystem->exists($themePath.'/'.ThemeHelper::HIDDEN_THEMES_TXT) && !$this->filesystem->exists($themePath.'/'.ThemeHelper::HIDDEN_THEMES_TXT.'.bkp')) {
            $this->filesystem->rename($themePath.'/'.ThemeHelper::HIDDEN_THEMES_TXT, $themePath.'/'.ThemeHelper::HIDDEN_THEMES_TXT.'.bkp');
        }
    }

    protected function beforeTearDown(): void
    {
        parent::beforeTearDown();

        $themePath = $this->pathsHelper->getThemesPath();

        if ($this->filesystem->exists($themePath.'/'.ThemeHelper::HIDDEN_THEMES_TXT.'.bkp') && !$this->filesystem->exists($themePath.'/'.ThemeHelper::HIDDEN_THEMES_TXT)) {
            $this->filesystem->rename($themePath.'/'.ThemeHelper::HIDDEN_THEMES_TXT.'.bkp', $themePath.'/'.ThemeHelper::HIDDEN_THEMES_TXT);
        }

        if ($this->filesystem->exists($themePath.'/blanktest')) {
            $this->filesystem->remove($themePath.'/blanktest');
        }

        if ($this->filesystem->exists($themePath.'/auroratest')) {
            $this->filesystem->remove($themePath.'/auroratest');
        }
    }

    public function testDeleteTheme(): void
    {
        $this->client->request(Request::METHOD_POST, 's/themes/batchDelete?ids=[%22aurora%22]');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('aurora is the default theme and therefore cannot be removed.', (string) $this->client->getResponse()->getContent());
    }

    public function testBatchDeleteActionValidation(): void
    {
        $this->client->request(Request::METHOD_POST, 's/themes/batchDelete?ids=[%22aurora%22,%22brienz%22]');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('aurora is the default theme and therefore cannot be removed.', (string) $this->client->getResponse()->getContent());
        $this->assertStringContainsString('brienz is the default theme and therefore cannot be removed.', (string) $this->client->getResponse()->getContent());
    }

    public function testBatchDeleteActionWithNonCoreTheme(): void
    {
        $themeHelper = self::getContainer()->get(ThemeHelper::class);
        $this->assertInstanceOf(ThemeHelper::class, $themeHelper);
        $themeHelper->copy('blank', 'blanktest');
        $themeHelper->copy('blank', 'auroratest');

        // Clear the private property 'themes' to reload themes.
        $reflectionClass = new \ReflectionClass(ThemeHelper::class);
        $themesProperty  = $reflectionClass->getProperty('themes');
        $themesProperty->setValue($themeHelper, []);

        $this->client->request(Request::METHOD_POST, '/s/themes/batchDelete?ids=[%22blanktest%22,%22auroratest%22]');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('2 themes have been deleted!', (string) $this->client->getResponse()->getContent(), $this->client->getResponse()->getContent());
    }

    public function testThemeVisibility(): void
    {
        // Email theme list has 'Aurora' theme
        $email = $this->client->request(Request::METHOD_GET, '/s/emails/new');
        $this->assertResponseIsSuccessful();
        $themesInEmail = $email->filterXPath('//div[@id="email-container"]');
        $this->assertStringContainsString('Aurora', $themesInEmail->html());

        // List themes
        $themeList = $this->client->request(Request::METHOD_GET, '/s/themes');
        $this->assertResponseIsSuccessful();

        // Theme list has 'Aurora' theme
        $themeRow = $themeList->filter('tr:contains("Aurora (aurora)")');
        $this->assertNotEmpty($themeRow);

        // Theme menu shows 'Hide' option
        $visibilityMenu = $themeRow->filter('ul')->filter('a[href="/s/themes/visibility/aurora"]');
        $this->assertStringContainsString('Hide', $visibilityMenu->html());

        // Hide the 'Aurora' theme
        $this->client->request(Request::METHOD_POST, '/s/themes/visibility/aurora');
        $this->assertResponseIsSuccessful();

        // Check if hidden-themes.txt file exists
        $themePath           = $this->pathsHelper->getThemesPath();
        $hiddenThemesTxtPath = $themePath.'/'.ThemeHelper::HIDDEN_THEMES_TXT;
        $this->assertFileExists($hiddenThemesTxtPath);

        // Check if hidden-themes.txt file contains hidden theme name 'Aurora (aurora)'
        $this->assertStringContainsString('|aurora', $this->filesystem->readFile($hiddenThemesTxtPath));

        // Reboot kernel to reload all themes
        self::bootKernel();
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertInstanceOf(User::class, $user);
        $this->loginUser($user);

        // Email theme list has hidden 'Aurora' theme
        $newEmail = $this->client->request(Request::METHOD_GET, '/s/emails/new');
        $this->assertResponseIsSuccessful();
        $hiddenThemesInEmail = $newEmail->filterXPath('//div[contains(@class, "theme-list") and contains(@class, "hide")]');
        $this->assertStringContainsString('Aurora', $hiddenThemesInEmail->html(), $newEmail->html());

        // List fresh themes
        $newThemeList = $this->client->request(Request::METHOD_GET, '/s/themes');
        $this->assertResponseIsSuccessful();

        // Check if 'Aurora' is the last theme in the list
        $hiddenThemeRow = $newThemeList->filter('table tr')->last();
        $this->assertNotEmpty($hiddenThemeRow);
        $this->assertStringContainsString('Aurora (aurora)', $hiddenThemeRow->html(), $newThemeList->html());

        // Check if 'Aurora' has 3 disabled table cells
        $disabledRowCell = $hiddenThemeRow->filter('td.disabled-row');
        $this->assertNotEmpty($disabledRowCell);
        $this->assertCount(3, $disabledRowCell);

        // Theme menu shows 'Unhide' option
        $visibilityMenu = $hiddenThemeRow->filter('ul')->filter('a[href="/s/themes/visibility/aurora"]');
        $this->assertStringContainsString('Show', $visibilityMenu->html());

        // Unhide the 'Aurora' theme
        $this->client->request(Request::METHOD_POST, '/s/themes/visibility/aurora');
        $this->assertResponseIsSuccessful();

        // Check if hidden-themes.txt file is removed since there was only one theme before
        $themePath           = $this->pathsHelper->getThemesPath();
        $hiddenThemesTxtPath = $themePath.'/'.ThemeHelper::HIDDEN_THEMES_TXT;
        $this->assertFileDoesNotExist($hiddenThemesTxtPath);
    }
}
