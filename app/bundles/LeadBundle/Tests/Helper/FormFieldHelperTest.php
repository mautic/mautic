<?php

namespace Mautic\LeadBundle\Tests\Helper;

use Mautic\LeadBundle\Helper\FormFieldHelper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class FormFieldHelperTest extends TestCase
{
    private $defaultUploadDir;

    protected function setUp()
    {
        $this->defaultUploadDir    = $_ENV['MAUTIC_UPLOAD_DIR'] ?? null;
        $_ENV['MAUTIC_UPLOAD_DIR'] = __DIR__; // may not be set unless Symfony is booted
    }

    protected function tearDown()
    {
        $_ENV['MAUTIC_UPLOAD_DIR'] = $this->defaultUploadDir;
    }

    public function testDefaultCountryList()
    {
        $list  = FormFieldHelper::getCountryChoices();
        $first = array_shift($list);
        $last  = array_pop($list);
        Assert::assertEquals('Afghanistan', $first);
        Assert::assertEquals('Zimbabwe', $last);
    }

    public function testCustomCountryList()
    {
        $_ENV['MAUTIC_UPLOAD_DIR'] = __DIR__.'/files';
        $list                      = FormFieldHelper::getCountryChoices();
        $first                     = array_shift($list);
        $last                      = array_pop($list);
        Assert::assertEquals('Middle Earth', $first);
        Assert::assertEquals('Fillory', $last);
    }

    public function testDefaultRegionList()
    {
        $list               = FormFieldHelper::getRegionChoices();
        $firstCountry       = array_shift($list);
        $firstCountryRegion = array_shift($firstCountry);
        $lastCountry        = array_pop($list);
        $lastCountryRegion  = array_pop($lastCountry);
        Assert::assertEquals('Alabama', $firstCountryRegion);
        Assert::assertEquals('St. Maarten', $lastCountryRegion);
    }

    public function testCustomRegionList()
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
