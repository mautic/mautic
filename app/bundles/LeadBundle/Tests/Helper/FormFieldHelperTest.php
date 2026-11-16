<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Helper;

use Mautic\LeadBundle\Helper\FormFieldHelper;
use PHPUnit\Framework\TestCase;

final class FormFieldHelperTest extends TestCase
{
    private ?string $defaultUploadDir;

    protected function setUp(): void
    {
        $this->defaultUploadDir    = $_ENV['MAUTIC_UPLOAD_DIR'] ?? null;
        $_ENV['MAUTIC_UPLOAD_DIR'] = __DIR__; // may not be set unless Symfony is booted
    }

    protected function tearDown(): void
    {
        $_ENV['MAUTIC_UPLOAD_DIR'] = $this->defaultUploadDir;
    }

    public function testDefaultCountryList(): void
    {
        $list  = FormFieldHelper::getCountryChoices();
        $first = array_shift($list);
        $last  = array_pop($list);
        $this->assertEquals('Afghanistan', $first);
        $this->assertEquals('Zimbabwe', $last);
    }

    public function testCustomCountryList(): void
    {
        $_ENV['MAUTIC_UPLOAD_DIR'] = __DIR__.'/files';
        $list                      = FormFieldHelper::getCountryChoices();
        $first                     = array_shift($list);
        $last                      = array_pop($list);
        $this->assertEquals('Middle Earth', $first);
        $this->assertEquals('Fillory', $last);
    }

    public function testDefaultRegionList(): void
    {
        $list               = FormFieldHelper::getRegionChoices();
        $firstCountry       = array_shift($list);
        $firstCountryRegion = array_shift($firstCountry);
        $lastCountry        = array_pop($list);
        $lastCountryRegion  = array_pop($lastCountry);
        $this->assertEquals('Alabama', $firstCountryRegion);
        $this->assertEquals('St. Maarten', $lastCountryRegion);
    }

    public function testCustomRegionList(): void
    {
        $_ENV['MAUTIC_UPLOAD_DIR'] = __DIR__.'/files';
        $list                      = FormFieldHelper::getRegionChoices();
        $firstCountry              = array_shift($list);
        $firstCountryRegion        = array_shift($firstCountry);
        $lastCountry               = array_pop($list);
        $lastCountryRegion         = array_pop($lastCountry);
        $this->assertEquals('The Westlands', $firstCountryRegion);
        $this->assertEquals('Darkling Woods', $lastCountryRegion);
    }
}
