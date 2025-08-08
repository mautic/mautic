<?php

namespace Mautic\CoreBundle\Tests\Unit\Twig\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Twig\Helper\AnalyticsHelper;
use PHPUnit\Framework\TestCase;

class AnalyticsHelperTest extends TestCase
{
    public function testGetFooterCode(): void
    {
        $coreParametersHelper = new class extends CoreParametersHelper {
            public function __construct()
            {
            }

            public function get($name, $default = null)
            {
                if ('footer_script' === $name) {
                    return '<script>footer</script>';
                }

                return null;
            }
        };

        $helper = new AnalyticsHelper($coreParametersHelper);
        $this->assertEquals('<script>footer</script>', $helper->getFooterCode());
    }

    public function testAddCodeWithFooterAnalytics(): void
    {
        $coreParametersHelper = new class extends CoreParametersHelper {
            public function __construct()
            {
            }

            public function get($name, $default = null)
            {
                if ('footer_script' === $name) {
                    return '<script>footer</script>';
                }

                return '';
            }
        };

        $helper = new AnalyticsHelper($coreParametersHelper);
        $result = $helper->addCode('<html><head></head><body>test</body></html>');
        $this->assertStringContainsString('<script>footer</script>', $result);
    }
}
