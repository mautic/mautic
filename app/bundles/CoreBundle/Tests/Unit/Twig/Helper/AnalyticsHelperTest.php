<?php

namespace Mautic\CoreBundle\Tests\Unit\Twig\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Twig\Helper\AnalyticsHelper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class AnalyticsHelperTest extends TestCase
{
    public function testGetCode(): void
    {
        $coreParametersHelper = new class extends CoreParametersHelper {
            public function __construct()
            {
            }

            public function get($name, $default = null)
            {
                if ('google_analytics' === $name) {
                    return '<script>console.log("analytics");</script>';
                }

                return null;
            }
        };

        $helper = new AnalyticsHelper($coreParametersHelper);

        Assert::assertEquals('<script>console.log("analytics");</script>', $helper->getCode());
    }

    public function testGetFooterCode(): void
    {
        $coreParametersHelper = new class extends CoreParametersHelper {
            public function __construct()
            {
            }

            public function get($name, $default = null)
            {
                if ('footer_script' === $name) {
                    return '<script>console.log("footer");</script>';
                }

                return null;
            }
        };

        $helper = new AnalyticsHelper($coreParametersHelper);

        Assert::assertEquals('<script>console.log("footer");</script>', $helper->getFooterCode());
    }

    public function testAddCodeWithNoHtml(): void
    {
        $coreParametersHelper = new class extends CoreParametersHelper {
            public function __construct()
            {
            }

            public function get($name, $default = null)
            {
                if ('google_analytics' === $name) {
                    return '<script>head</script>';
                }
                if ('footer_script' === $name) {
                    return '<script>footer</script>';
                }

                return null;
            }
        };

        $helper = new AnalyticsHelper($coreParametersHelper);

        $content = 'Simple text content';
        $result  = $helper->addCode($content);

        Assert::assertEquals("<html>\n<head><script>head</script></head>\n<body>Simple text content<script>footer</script></body>\n</html>", $result);
    }

    public function testAddCodeWithHtmlNoHead(): void
    {
        $coreParametersHelper = new class extends CoreParametersHelper {
            public function __construct()
            {
            }

            public function get($name, $default = null)
            {
                if ('google_analytics' === $name) {
                    return '<script>head</script>';
                }
                if ('footer_script' === $name) {
                    return '<script>footer</script>';
                }

                return null;
            }
        };

        $helper = new AnalyticsHelper($coreParametersHelper);

        $content = '<html><body>Content</body></html>';
        $result  = $helper->addCode($content);

        Assert::assertEquals("<html>\n<head>\n<script>head</script>\n</head><body>Content<script>footer</script>\n</body></html>", $result);
    }

    public function testAddCodeWithFullHtml(): void
    {
        $coreParametersHelper = new class extends CoreParametersHelper {
            public function __construct()
            {
            }

            public function get($name, $default = null)
            {
                if ('google_analytics' === $name) {
                    return '<script>head</script>';
                }
                if ('footer_script' === $name) {
                    return '<script>footer</script>';
                }

                return null;
            }
        };

        $helper = new AnalyticsHelper($coreParametersHelper);

        $content = '<html><head><title>Test</title></head><body>Content</body></html>';
        $result  = $helper->addCode($content);

        Assert::assertEquals('<html><head><title>Test</title><script>head</script>'."\n".'</head><body>Content<script>footer</script>'."\n".'</body></html>', $result);
    }

    public function testAddCodeWithEmptyScripts(): void
    {
        $coreParametersHelper = new class extends CoreParametersHelper {
            public function __construct()
            {
            }

            public function get($name, $default = null)
            {
                return '';
            }
        };

        $helper = new AnalyticsHelper($coreParametersHelper);

        $content = '<html><head><title>Test</title></head><body>Content</body></html>';
        $result  = $helper->addCode($content);

        Assert::assertEquals('<html><head><title>Test</title></head><body>Content</body></html>', $result);
    }

    public function testGetName(): void
    {
        $coreParametersHelper = new class extends CoreParametersHelper {
            public function __construct()
            {
            }

            public function get($name, $default = null)
            {
                return null;
            }
        };

        $helper = new AnalyticsHelper($coreParametersHelper);

        Assert::assertEquals('analytics', $helper->getName());
    }
}
