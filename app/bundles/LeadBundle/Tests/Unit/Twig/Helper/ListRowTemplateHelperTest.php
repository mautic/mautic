<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Unit\Twig\Helper;

use Mautic\LeadBundle\Twig\Helper\ListRowTemplateHelper;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class ListRowTemplateHelperTest extends TestCase
{
    private const VIEWS_DIR = __DIR__.'/../../../../Resources/views';

    private string $themeDir;

    protected function setUp(): void
    {
        $this->themeDir = sys_get_temp_dir().'/list-row-override-'.bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        $file = $this->themeDir.'/List/_list_row.html.twig';
        if (is_file($file)) {
            unlink($file);
        }
        if (is_dir($this->themeDir.'/List')) {
            rmdir($this->themeDir.'/List');
        }
        if (is_dir($this->themeDir)) {
            rmdir($this->themeDir);
        }
    }

    public function testDefaultRowTemplateIsNotReportedAsOverridden(): void
    {
        $loader = new FilesystemLoader();
        $loader->addPath(self::VIEWS_DIR, 'MauticLead');

        $this->assertFalse((new ListRowTemplateHelper(new Environment($loader)))->isOverridden());
    }

    public function testThemeRowTemplateIsReportedAsOverridden(): void
    {
        mkdir($this->themeDir.'/List', 0777, true);
        file_put_contents($this->themeDir.'/List/_list_row.html.twig', '<tr></tr>');

        $loader = new FilesystemLoader();
        $loader->addPath(self::VIEWS_DIR, 'MauticLead');
        $loader->prependPath($this->themeDir, 'MauticLead');

        $this->assertTrue((new ListRowTemplateHelper(new Environment($loader)))->isOverridden());
    }
}
