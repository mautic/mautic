<?php

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\TrailingSlashHelper;
use Symfony\Component\HttpFoundation\Request;

class TrailingSlashHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CoreParametersHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $coreParametersHelper;

    protected function setUp(): void
    {
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->coreParametersHelper->method('get')
            ->with('site_url')
            ->willReturn('https://test.com');
    }

    public function testOpenRedirectIsNotPossible()
    {
        $server = [
            'HTTP_HOST'       => 'test.com',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.56 Safari/537.36',
            'SERVER_NAME'     => 'test.com',
            'SERVER_ADDR'     => '::1',
            'SERVER_PORT'     => '80',
            'REMOTE_ADDR'     => '::1',
            'DOCUMENT_ROOT'   => null,
            'REQUEST_SCHEME'  => 'http',
            'REMOTE_PORT'     => '80',
            'REDIRECT_URL'    => '/google.com/',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_METHOD'  => 'GET',
            'QUERY_STRING'    => '',
            'REQUEST_URI'     => '//google.com/',
            'SCRIPT_NAME'     => '/index.php',
            'PHP_SELF'        => '/index.php',
        ];

        $request = new Request([], [], [], [], [], $server);

        // google.com should not be returned as the URL
        $this->assertEquals('https://test.com//google.com', $this->getHelper()->getSafeRedirectUrl($request));
    }

    public function testMauticUrlWithTrailingSlashIsGeneratedCorrectly()
    {
        $server = [
            'HTTP_HOST'       => 'test.com',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.56 Safari/537.36',
            'SERVER_NAME'     => 'test.com',
            'SERVER_ADDR'     => '::1',
            'SERVER_PORT'     => '80',
            'REMOTE_ADDR'     => '::1',
            'DOCUMENT_ROOT'   => null,
            'REQUEST_SCHEME'  => 'http',
            'REMOTE_PORT'     => '80',
            'REDIRECT_URL'    => '/s/dashboard/',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_METHOD'  => 'GET',
            'QUERY_STRING'    => '',
            'REQUEST_URI'     => '/s/dashboard/',
            'SCRIPT_NAME'     => '/index.php',
            'PHP_SELF'        => '/index.php',
        ];

        $request = new Request([], [], [], [], [], $server);

        // google.com should not be returned as the URL
        $this->assertEquals('https://test.com/s/dashboard', $this->getHelper()->getSafeRedirectUrl($request));
    }

    /**
     * @return TrailingSlashHelper
     */
    private function getHelper()
    {
        return new TrailingSlashHelper($this->coreParametersHelper);
    }
}
