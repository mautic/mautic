<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Helper\Filesystem;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\UserEntityTrait;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

final class ThemeControllerTest extends MauticMysqlTestCase
{
    use UserEntityTrait;

    private PathsHelper $pathsHelper;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->configParams['legacy_builder_enabled'] = true;

        // The Custom Theme tab is disabled by default.
        if ('testCustomTheme' === $this->name()) {
            $this->configParams['theme_custom_tab_enabled'] = true;
        }

        parent::setUp();

        $this->pathsHelper = $this->getContainer()->get(PathsHelper::class);
        $this->assertInstanceOf(PathsHelper::class, $this->pathsHelper);

        $this->filesystem  = $this->getContainer()->get(Filesystem::class);
        $this->assertInstanceOf(Filesystem::class, $this->filesystem);

        $themePath = $this->pathsHelper->getThemesPath();

        if ($this->filesystem->exists($themePath.'/'.ThemeHelper::HIDDEN_THEMES_TXT) && !$this->filesystem->exists($themePath.'/'.ThemeHelper::HIDDEN_THEMES_TXT.'.bkp')) {
            $this->filesystem->rename($themePath.'/'.ThemeHelper::HIDDEN_THEMES_TXT, $themePath.'/'.ThemeHelper::HIDDEN_THEMES_TXT.'.bkp');
        }

        if ($this->filesystem->exists($themePath.'/'.ThemeHelper::CUSTOM_THEMES_TXT) && !$this->filesystem->exists($themePath.'/'.ThemeHelper::CUSTOM_THEMES_TXT.'.bkp')) {
            $this->filesystem->rename($themePath.'/'.ThemeHelper::CUSTOM_THEMES_TXT, $themePath.'/'.ThemeHelper::CUSTOM_THEMES_TXT.'.bkp');
        }
    }

    protected function beforeTearDown(): void
    {
        parent::beforeTearDown();

        $themePath = $this->pathsHelper->getThemesPath();

        if ($this->filesystem->exists($themePath.'/'.ThemeHelper::HIDDEN_THEMES_TXT.'.bkp') && !$this->filesystem->exists($themePath.'/'.ThemeHelper::HIDDEN_THEMES_TXT)) {
            $this->filesystem->rename($themePath.'/'.ThemeHelper::HIDDEN_THEMES_TXT.'.bkp', $themePath.'/'.ThemeHelper::HIDDEN_THEMES_TXT);
        }

        // Remove the file created by a test and restore the original file.
        $this->filesystem->remove($themePath.'/'.ThemeHelper::CUSTOM_THEMES_TXT);
        if ($this->filesystem->exists($themePath.'/'.ThemeHelper::CUSTOM_THEMES_TXT.'.bkp')) {
            $this->filesystem->rename($themePath.'/'.ThemeHelper::CUSTOM_THEMES_TXT.'.bkp', $themePath.'/'.ThemeHelper::CUSTOM_THEMES_TXT);
        }

        $this->filesystem->remove($themePath.'/MixedCaseTest');

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

    /**
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function provideEditorsWithThemeTab(): iterable
    {
        // Editor form URL, ID of the Theme tab, key and name of a theme which the editor can use.
        yield 'email'        => ['/s/emails/new', 'email-container', 'aurora', 'Aurora'];
        yield 'landing page' => ['/s/pages/new', 'theme-container', 'oxygen', 'Oxygen'];
    }

    #[DataProvider('provideEditorsWithThemeTab')]
    public function testCustomTheme(string $editorUrl, string $themeTabId, string $themeKey, string $themeName): void
    {
        $customThemesTxtPath = $this->pathsHelper->getThemesPath().'/'.ThemeHelper::CUSTOM_THEMES_TXT;
        $themeRowSelector    = sprintf('tr:contains("%s (%s)")', $themeName, $themeKey);
        $customActionUrl     = '/s/themes/custom/'.$themeKey;

        // At first the theme is in the Theme tab. The Custom Theme tab is empty.
        $editor = $this->client->request(Request::METHOD_GET, $editorUrl);
        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $editor->filter('a[href="#custom-theme-container"]'));
        $this->assertStringContainsString('Custom Theme', $editor->filter('a[href="#custom-theme-container"]')->text());
        $this->assertContains($themeName, $this->getThemeCardTitles($editor, $themeTabId));
        $this->assertNotContains($themeName, $this->getThemeCardTitles($editor, 'custom-theme-container'));
        $this->assertCount(1, $editor->filter('#custom-theme-container #custom-theme-empty'));

        // The theme list shows the option to set the theme as custom theme, but no icon.
        $themeList = $this->client->request(Request::METHOD_GET, '/s/themes');
        $this->assertResponseIsSuccessful();
        $themeRow = $themeList->filter($themeRowSelector);
        $this->assertNotEmpty($themeRow);
        $customMenu = $themeRow->filter('ul')->filter(sprintf('a[href="%s"]', $customActionUrl));
        $this->assertStringContainsString('Set as custom theme', $customMenu->html());
        $this->assertCount(0, $themeRow->filter('#custom-theme-icon-'.$themeKey));

        // Set the theme as custom theme.
        $this->setCsrfHeader();
        $this->client->request(Request::METHOD_POST, $customActionUrl);
        $this->assertResponseIsSuccessful();
        $this->assertFileExists($customThemesTxtPath);
        $this->assertSame($themeKey, $this->filesystem->readFile($customThemesTxtPath));

        $this->rebootKernelAndLoginAsAdmin();

        // The theme moved to the Custom Theme tab. The code mode card is shown only once.
        $editor = $this->client->request(Request::METHOD_GET, $editorUrl);
        $this->assertResponseIsSuccessful();
        $this->assertNotContains($themeName, $this->getThemeCardTitles($editor, $themeTabId));
        $this->assertContains($themeName, $this->getThemeCardTitles($editor, 'custom-theme-container'));
        $this->assertCount(0, $editor->filter('#custom-theme-empty'));
        $this->assertCount(1, $editor->filter('a[data-theme="mautic_code_mode"]'));

        // The theme list shows the icon and the option to remove the custom theme.
        $themeList = $this->client->request(Request::METHOD_GET, '/s/themes');
        $this->assertResponseIsSuccessful();
        $themeRow = $themeList->filter($themeRowSelector);
        $this->assertNotEmpty($themeRow);
        $this->assertCount(1, $themeRow->filter('#custom-theme-icon-'.$themeKey));
        $customMenu = $themeRow->filter('ul')->filter(sprintf('a[href="%s"]', $customActionUrl));
        $this->assertStringContainsString('Remove from custom themes', $customMenu->html());

        // Remove the custom theme setting with the same action.
        $this->setCsrfHeader();
        $this->client->request(Request::METHOD_POST, $customActionUrl);
        $this->assertResponseIsSuccessful();
        $this->assertFileDoesNotExist($customThemesTxtPath);

        $this->rebootKernelAndLoginAsAdmin();

        // The theme is back in the Theme tab.
        $editor = $this->client->request(Request::METHOD_GET, $editorUrl);
        $this->assertResponseIsSuccessful();
        $this->assertContains($themeName, $this->getThemeCardTitles($editor, $themeTabId));
        $this->assertNotContains($themeName, $this->getThemeCardTitles($editor, 'custom-theme-container'));
        $this->assertCount(1, $editor->filter('#custom-theme-empty'));
    }

    #[DataProvider('provideEditorsWithThemeTab')]
    public function testCustomThemeIsShownInThemeTabWhenCustomThemeTabIsDisabled(string $editorUrl, string $themeTabId, string $themeKey, string $themeName): void
    {
        $this->filesystem->dumpFile($this->pathsHelper->getThemesPath().'/'.ThemeHelper::CUSTOM_THEMES_TXT, $themeKey);

        // The theme is still a custom theme: the theme list shows the icon.
        $themeList = $this->client->request(Request::METHOD_GET, '/s/themes');
        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $themeList->filter(sprintf('tr:contains("%s (%s)") #custom-theme-icon-%s', $themeName, $themeKey, $themeKey)));

        // The editor has no Custom Theme tab, and the theme is not lost: it shows in the Theme tab.
        $editor = $this->client->request(Request::METHOD_GET, $editorUrl);
        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $editor->filter('a[href="#custom-theme-container"]'));
        $this->assertCount(0, $editor->filter('#custom-theme-container'));
        $this->assertContains($themeName, $this->getThemeCardTitles($editor, $themeTabId));
    }

    public function testCustomThemeWithUppercaseLettersInName(): void
    {
        $themesPath = $this->pathsHelper->getThemesPath();
        $this->filesystem->mirror($themesPath.'/blank', $themesPath.'/MixedCaseTest');

        $this->setCsrfHeader();
        $this->client->request(Request::METHOD_POST, '/s/themes/custom/MixedCaseTest');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('is now a custom theme', (string) $this->client->getResponse()->getContent());
        $this->assertSame('MixedCaseTest', $this->filesystem->readFile($themesPath.'/'.ThemeHelper::CUSTOM_THEMES_TXT));
    }

    public function testCustomThemeRequiresValidCsrfToken(): void
    {
        $customThemesTxtPath = $this->pathsHelper->getThemesPath().'/'.ThemeHelper::CUSTOM_THEMES_TXT;

        // No token.
        $this->client->request(Request::METHOD_POST, '/s/themes/custom/aurora');
        $this->assertResponseStatusCodeSame(403);
        $this->assertFileDoesNotExist($customThemesTxtPath);

        // Wrong token.
        $this->client->setServerParameter('HTTP_X-CSRF-Token', 'invalid-token');
        $this->client->request(Request::METHOD_POST, '/s/themes/custom/aurora');
        $this->assertResponseStatusCodeSame(403);
        $this->assertFileDoesNotExist($customThemesTxtPath);
    }

    public function testCustomThemeNotFound(): void
    {
        $this->setCsrfHeader();
        $this->client->request(Request::METHOD_POST, '/s/themes/custom/does-not-exist');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Theme does-not-exist not found.', (string) $this->client->getResponse()->getContent());
        $this->assertFileDoesNotExist($this->pathsHelper->getThemesPath().'/'.ThemeHelper::CUSTOM_THEMES_TXT);
    }

    public function testCustomThemeRequiresEditPermission(): void
    {
        // The user can view the themes, but cannot edit them.
        $user = $this->createUserWithPermission([
            'user-name'  => 'theme-viewer',
            'email'      => 'theme-viewer@mautic-test.com',
            'first-name' => 'Theme',
            'last-name'  => 'Viewer',
            'role'       => [
                'name'        => 'theme_viewer',
                'permissions' => [
                    'core:themes' => 4,
                ],
            ],
        ]);

        $this->em->flush();
        $this->em->clear();

        $this->loginOtherUser($user);

        $themeList = $this->client->request(Request::METHOD_GET, '/s/themes');
        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $themeList->filter('a[href="/s/themes/custom/aurora"]'));

        // The token is valid, so only the missing permission can cause the 403.
        $this->setCsrfHeader();
        $this->client->request(Request::METHOD_POST, '/s/themes/custom/aurora');
        $this->assertResponseStatusCodeSame(403);
        $this->assertFileDoesNotExist($this->pathsHelper->getThemesPath().'/'.ThemeHelper::CUSTOM_THEMES_TXT);
    }

    /**
     * The Theme tab also contains the hidden select of the theme field, which lists every theme.
     * So the tests look at the titles of the theme cards.
     *
     * @return string[]
     */
    private function getThemeCardTitles(Crawler $editor, string $tabId): array
    {
        return $editor->filter(sprintf('#%s .theme-list h3', $tabId))->each(static fn (Crawler $title): string => trim($title->text()));
    }

    /**
     * The theme helper keeps the details of the themes in memory, so the next request needs a new kernel.
     */
    private function rebootKernelAndLoginAsAdmin(): void
    {
        self::bootKernel();
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertInstanceOf(User::class, $user);
        $this->loginUser($user);
    }
}
