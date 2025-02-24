<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Helper;

use Mautic\LeadBundle\Helper\FormFieldHelper;
use PHPUnit\Framework\Assert;
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
        Assert::assertEquals('Afghanistan', $first);
        Assert::assertEquals('Zimbabwe', $last);
    }

    public function testCustomCountryList(): void
    {
        $_ENV['MAUTIC_UPLOAD_DIR'] = __DIR__.'/files';
        $list                      = FormFieldHelper::getCountryChoices();
        $first                     = array_shift($list);
        $last                      = array_pop($list);
        Assert::assertEquals('Middle Earth', $first);
        Assert::assertEquals('Fillory', $last);
    }

    public function testDefaultRegionList(): void
    {
        $list               = FormFieldHelper::getRegionChoices();
        $firstCountry       = array_shift($list);
        $firstCountryRegion = array_shift($firstCountry);
        $lastCountry        = array_pop($list);
        $lastCountryRegion  = array_pop($lastCountry);
        Assert::assertEquals('Alabama', $firstCountryRegion);
        Assert::assertEquals('St. Maarten', $lastCountryRegion);
    }

    public function testCustomRegionList(): void
    {
        $_ENV['MAUTIC_UPLOAD_DIR'] = __DIR__.'/files';
        $list                      = FormFieldHelper::getRegionChoices();
        $firstCountry              = array_shift($list);
        $firstCountryRegion        = array_shift($firstCountry);
        $lastCountry               = array_pop($list);
        $lastCountryRegion         = array_pop($lastCountry);
        Assert::assertEquals('The Westlands', $firstCountryRegion);
        Assert::assertEquals('Darkling Woods', $lastCountryRegion);
    }
}
