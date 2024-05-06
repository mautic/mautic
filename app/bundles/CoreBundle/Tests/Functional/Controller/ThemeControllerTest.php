<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Helper\Filesystem;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ThemeControllerTest extends MauticMysqlTestCase
{
    private PathsHelper $pathsHelper;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->configParams['legacy_builder_enabled'] = true;
        parent::setUp();

        $this->pathsHelper = $this->getContainer()->get('mautic.helper.paths');
        \assert($this->pathsHelper instanceof PathsHelper);
        $this->filesystem  = $this->getContainer()->get('mautic.filesystem');
        \assert($this->filesystem instanceof Filesystem);

        $themePath = $this->pathsHelper->getThemesPath();

        if ($this->filesystem->exists($themePath.'/'.ThemeHelper::HIDDEN_THEMES_TXT)) {
            $this->filesystem->rename($themePath.'/'.ThemeHelper::HIDDEN_THEMES_TXT, $themePath.'/'.ThemeHelper::HIDDEN_THEMES_TXT.'.bkp');
        }
    }

    protected function beforeTearDown(): void
    {
        parent::beforeTearDown();

        $themePath = $this->pathsHelper->getThemesPath();

        if ($this->filesystem->exists($themePath.'/'.ThemeHelper::HIDDEN_THEMES_TXT.'.bkp')) {
            $this->filesystem->rename($themePath.'/'.ThemeHelper::HIDDEN_THEMES_TXT.'.bkp', $themePath.'/'.ThemeHelper::HIDDEN_THEMES_TXT);
        }
    }

    public function testDeleteTheme(): void
    {
        $this->client->request(Request::METHOD_POST, 's/themes/batchDelete?ids=[%22aurora%22]');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
        $this->assertStringContainsString('aurora is the default theme and therefore cannot be removed.', $this->client->getResponse()->getContent());
    }

    public function testThemeVisibility(): void
    {
        // Landing page theme list has 'Sunday V2' theme
        $page = $this->client->request(Request::METHOD_GET, '/s/pages/new');
        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
        $themesInPage = $page->filterXPath('//div[@id="theme-container"]');
        Assert::assertStringContainsString('Sunday V2', $themesInPage->html());

        // Email theme list has 'Sunday V2' theme
        $email = $this->client->request(Request::METHOD_GET, '/s/emails/new');
        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
        $themesInEmail = $email->filterXPath('//div[@id="email-container"]');
        Assert::assertStringContainsString('Sunday V2', $themesInEmail->html());

        // List themes
        $themeList = $this->client->request(Request::METHOD_GET, '/s/themes');
        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        // Theme list has 'Sunday V2' theme
        $themeRow = $themeList->filter('tr:contains("Sunday V2 (sunday)")');
        Assert::assertNotEmpty($themeRow);

        // Theme menu shows 'Hide' option
        $visibilityMenu = $themeRow->filter('ul')->filter('a[href="/s/themes/visibility/sunday"]');
        Assert::assertStringContainsString('Hide', $visibilityMenu->html());

        // Hide the 'Sunday V2' theme
        $this->client->request(Request::METHOD_POST, '/s/themes/visibility/sunday');
        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        // Check if hidden-themes.txt file exists
        $themePath           = $this->pathsHelper->getThemesPath();
        $hiddenThemesTxtPath = $themePath.'/'.ThemeHelper::HIDDEN_THEMES_TXT;
        Assert::assertFileExists($hiddenThemesTxtPath);

        // Check if hidden-themes.txt file contains hidden theme name 'Sunday V2 (sunday)'
        Assert::assertStringContainsString('|sunday', $this->filesystem->readFile($hiddenThemesTxtPath));

        // Reboot kernel to reload all themes
        self::bootKernel();
        $this->loginUser('admin');

        // Landing page theme list has hidden 'Sunday V2' theme
        $newPage = $this->client->request(Request::METHOD_GET, '/s/pages/new');
        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
        $hiddenThemesInPage = $newPage->filterXPath('//div[contains(@class, "theme-list") and contains(@class, "hide")]');
        Assert::assertStringContainsString('Sunday V2', $hiddenThemesInPage->html());

        // Email theme list has hidden 'Sunday V2' theme
        $newEmail = $this->client->request(Request::METHOD_GET, '/s/emails/new');
        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
        $hiddenThemesInEmail = $newEmail->filterXPath('//div[contains(@class, "theme-list") and contains(@class, "hide")]');
        Assert::assertStringContainsString('Sunday V2', $hiddenThemesInEmail->html());

        // List fresh themes
        $newThemeList = $this->client->request(Request::METHOD_GET, '/s/themes');
        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        // Check if 'Sunday V2' is the last theme in the list
        $hiddenThemeRow = $newThemeList->filter('table tr')->last();
        Assert::assertNotEmpty($hiddenThemeRow);
        Assert::assertStringContainsString('Sunday V2 (sunday)', $hiddenThemeRow->html());

        // Check if 'Sunday V2' has 3 disabled table cells
        $disabledRowCell = $hiddenThemeRow->filter('td.disabled-row');
        Assert::assertNotEmpty($disabledRowCell);
        Assert::assertCount(3, $disabledRowCell);

        // Theme menu shows 'Unhide' option
        $visibilityMenu = $hiddenThemeRow->filter('ul')->filter('a[href="/s/themes/visibility/sunday"]');
        Assert::assertStringContainsString('Unhide', $visibilityMenu->html());

        // Unhide the 'Sunday V2' theme
        $this->client->request(Request::METHOD_POST, '/s/themes/visibility/sunday');
        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        // Check if hidden-themes.txt file is removed since there was only one theme before
        $themePath           = $this->pathsHelper->getThemesPath();
        $hiddenThemesTxtPath = $themePath.'/'.ThemeHelper::HIDDEN_THEMES_TXT;
        Assert::assertFileDoesNotExist($hiddenThemesTxtPath);
    }
}
