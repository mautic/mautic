<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Twig;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Twig\Extension\ThemesExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ThemesExtensionTest extends TestCase
{
    private MockObject&CoreParametersHelper $coreParametersHelper;

    private ThemesExtension $themesExtension;

    protected function setUp(): void
    {
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->themesExtension      = new ThemesExtension($this->coreParametersHelper);
    }

    public function testGetBrandPrimaryColor(): void
    {
        $this->coreParametersHelper->expects($this->any())
            ->method('get')
            ->with('primary_brand_color', '000000')
            ->willReturn('123456');

        $this->assertEquals('123456', $this->themesExtension->getBrandPrimaryColor());
    }

    public function testGetTextOnBrandColor(): void
    {
        $this->coreParametersHelper->expects($this->any())
            ->method('get')
            ->with('primary_brand_color', '000000')
            ->willReturnOnConsecutiveCalls('000000', 'ffffff');

        // First assertion: primary color is '000000', text color should be 'ffffff'
        $this->assertSame('ffffff', $this->themesExtension->getTextOnBrandColor());

        // Second assertion: primary color is 'ffffff', text color should be '000000'
        $this->assertEquals('000000', $this->themesExtension->getTextOnBrandColor());
    }

    public function testGetTextOnBrandHelperColor(): void
    {
        // First call: primary color is '000000'
        $this->coreParametersHelper->expects($this->any())
            ->method('get')
            ->with('primary_brand_color', '000000')
            ->willReturnOnConsecutiveCalls('000000', 'ffffff');

        // First assertion: text color should be 'ffffff', helper color should be 'b3b3b3'
        $this->assertSame('b3b3b3', $this->themesExtension->getTextOnBrandHelperColor());

        // Second assertion: text color should be '000000', helper color should be '6d6d6d'
        $this->assertSame('6d6d6d', $this->themesExtension->getTextOnBrandHelperColor());
    }

    public function testGetRoundedCorners(): void
    {
        // First set of assertions: rounded corners set to 8
        $this->coreParametersHelper->expects($this->any())
            ->method('get')
            ->with('rounded_corners', 0)
            ->willReturn(8);

        $this->assertSame(8, $this->themesExtension->getRoundedCorners('lg'));
        $this->assertSame(4, $this->themesExtension->getRoundedCorners('md'));
        $this->assertSame(3, $this->themesExtension->getRoundedCorners('sm'));

        // Reset the mock for the next set of assertions
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->themesExtension      = new ThemesExtension($this->coreParametersHelper);

        // Second set of assertions: rounded corners set to 16
        $this->coreParametersHelper->expects($this->any())
            ->method('get')
            ->with('rounded_corners', 0)
            ->willReturn(16);

        $this->assertSame(16, $this->themesExtension->getRoundedCorners('lg'));
        $this->assertSame(6, $this->themesExtension->getRoundedCorners('md'));
        $this->assertSame(4, $this->themesExtension->getRoundedCorners('sm'));

        // Reset the mock for the next set of assertions
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->themesExtension      = new ThemesExtension($this->coreParametersHelper);

        // Third set of assertions: rounded corners set to 32
        $this->coreParametersHelper->expects($this->any())
            ->method('get')
            ->with('rounded_corners', 0)
            ->willReturn(32);

        $this->assertSame(32, $this->themesExtension->getRoundedCorners('lg'));
        $this->assertSame(8, $this->themesExtension->getRoundedCorners('md'));
        $this->assertSame(5, $this->themesExtension->getRoundedCorners('sm'));
    }
}
