<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Unit\Twig\Helper;

use Mautic\LeadBundle\Twig\Helper\ListRowTemplateHelper;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class ListRowTemplateHelperTest extends TestCase
{
    private const TEMPLATE_PATH = 'List/_list_row.html.twig';

    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/list-row-override-'.bin2hex(random_bytes(6));
        $this->writeTemplate('default');
        $this->writeTemplate('theme');
    }

    protected function tearDown(): void
    {
        foreach (['default', 'theme'] as $directory) {
            @unlink($this->root.'/'.$directory.'/'.self::TEMPLATE_PATH);
            @rmdir($this->root.'/'.$directory.'/List');
            @rmdir($this->root.'/'.$directory);
        }
        @rmdir($this->root);
    }

    public function testDefaultRowTemplateIsNotReportedAsOverridden(): void
    {
        $loader = new FilesystemLoader();
        $loader->addPath($this->root.'/default', 'MauticLead');

        $this->assertFalse($this->helper($loader)->isOverridden());
    }

    public function testThemeRowTemplateIsReportedAsOverridden(): void
    {
        $loader = new FilesystemLoader();
        $loader->addPath($this->root.'/default', 'MauticLead');
        $loader->prependPath($this->root.'/theme', 'MauticLead');

        $this->assertTrue($this->helper($loader)->isOverridden());
    }

    private function helper(FilesystemLoader $loader): ListRowTemplateHelper
    {
        return new ListRowTemplateHelper(new Environment($loader), $this->root.'/default/'.self::TEMPLATE_PATH);
    }

    private function writeTemplate(string $directory): void
    {
        mkdir($this->root.'/'.$directory.'/List', 0777, true);
        file_put_contents($this->root.'/'.$directory.'/'.self::TEMPLATE_PATH, '<tr></tr>');
    }
}
