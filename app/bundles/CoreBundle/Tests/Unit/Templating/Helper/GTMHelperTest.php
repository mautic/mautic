<?php

namespace Mautic\CoreBundle\Tests\Unit\Templating\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

class GTMHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CoreParametersHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $parametersHelper;

    protected function setUp(): void
    {
        $this->parametersHelper = $this->createMock(CoreParametersHelper::class);
    }

    /**
     * @return void
     */
    public function testGetCodeAndHasLandingEnabledEmpty()
    {
        $helper = new \Mautic\CoreBundle\Templating\Helper\GTMHelper($this->parametersHelper);

        $this->assertEquals(null, $helper->getCode());
        $this->assertEquals(false, $helper->hasLandingPageEnabled());
    }

    /**
     * @return void
     */
    public function testGetCodeAndHasLandingEnabled()
    {
        $this->parametersHelper->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                'gtm_id', true
            );

        $helper = new \Mautic\CoreBundle\Templating\Helper\GTMHelper($this->parametersHelper);
        $this->assertEquals('gtm_id', $helper->getCode());
        $this->assertEquals(true, $helper->hasLandingPageEnabled());
    }

    /**
     * @return void
     */
    public function testGetName()
    {
        $helper = new \Mautic\CoreBundle\Templating\Helper\GTMHelper($this->parametersHelper);
        $this->assertSame('google_tag_manager', $helper->getName());
    }

    /**
     * @return void
     */
    public function testGetBodyGTMCodeWithCorrectCode()
    {
        $code = 'gtm_id';
        $this->parametersHelper->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                $code
            );

        $helper = new \Mautic\CoreBundle\Templating\Helper\GTMHelper($this->parametersHelper);

        $js = '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id='.$code.'"
            height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>';

        $this->assertSame($js, $helper->getBodyGTMCode());
    }

    /**
     * @return void
     */
    public function testGetBodyGTMCodeWithWrongCode()
    {
        $correctCode = 'gtm_id';
        $wrongCode   = 'gtm_wrong_id';

        $this->parametersHelper->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                $correctCode
            );

        $helper = new \Mautic\CoreBundle\Templating\Helper\GTMHelper($this->parametersHelper);

        $js = '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id='.$wrongCode.'"
            height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>';

        $this->assertNotEquals($js, $helper->getBodyGTMCode());
    }

    /**
     * @return void
     */
    public function testGetBodyGTMCodeWithEmptyCode()
    {
        $helper = new \Mautic\CoreBundle\Templating\Helper\GTMHelper($this->parametersHelper);

        $this->assertSame('', $helper->getBodyGTMCode());
    }

    /**
     * @return void
     */
    public function testGetHeadGTMCodeWithCorrectCode()
    {
        $code = 'gtm_id';
        $this->parametersHelper->expects($this->exactly(2))
        ->method('get')
        ->willReturnOnConsecutiveCalls(
            $code
        );

        $helper = new \Mautic\CoreBundle\Templating\Helper\GTMHelper($this->parametersHelper);

        $js = "
            <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','{$code}');</script>";

        $this->assertSame($js, $helper->getHeadGTMCode());
    }

    /**
     * @return void
     */
    public function testGetHeadGTMCodeWithWrongCode()
    {
        $correctCode = 'gtm_id';
        $wrongCode   = 'gtm_wrong_id';
        $this->parametersHelper->expects($this->exactly(2))
        ->method('get')
        ->willReturnOnConsecutiveCalls(
            $correctCode
        );

        $helper = new \Mautic\CoreBundle\Templating\Helper\GTMHelper($this->parametersHelper);

        $js = "
            <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','{$wrongCode}');</script>";

        $this->assertNotEquals($js, $helper->getHeadGTMCode());
    }

    /**
     * @return void
     */
    public function testGetHeadGTMCodeWithEmptyCode()
    {
        $helper = new \Mautic\CoreBundle\Templating\Helper\GTMHelper($this->parametersHelper);

        $this->assertSame('', $helper->getHeadGTMCode());
    }
}
