<?php

namespace MauticPlugin\MauticFocusBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use MauticPlugin\MauticFocusBundle\Helper\IframeAvailabilityChecker;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class IframeAvailabilityCheckerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&TranslatorInterface
     */
    private MockObject $translator;

    /**
     * @var MockObject&HttpClientInterface
     */
    private MockObject $httpClient;

    /**
     * @var MockObject&CoreParametersHelper
     */
    private MockObject $parametersHelper;

    private IframeAvailabilityChecker $helper;

    public function setUp(): void
    {
        $this->translator       = $this->createMock(TranslatorInterface::class);
        $this->httpClient       = $this->createMock(HttpClientInterface::class);
        $this->parametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->helper           = new IframeAvailabilityChecker($this->translator, $this->httpClient, $this->parametersHelper);
    }

    public function testCheckProtocolMismatch(): void
    {
        $currentScheme           = 'https';
        $url                     = 'http://google.com'; // NOSONAR
        $translatedErrorMessage  = 'error';
        $expectedResponseContent = [
            'status'       => 0,
            'errorMessage' => $translatedErrorMessage,
        ];

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                'mautic.focus.protocol.mismatch',
                [
                    '%url%' => str_replace('http://', 'https://', $url),
                ]
            )
            ->willReturn($translatedErrorMessage);

        $this->httpClient->expects($this->never())
            ->method('request');

        $this->parametersHelper->expects($this->never())
            ->method('get');

        $response = $this->helper->check($url, $currentScheme);

        $responseBody = json_decode($response->getContent(), true);
        $this->assertEquals($expectedResponseContent, $responseBody);
    }

    public function testCheckLeadsToException(): void
    {
        $currentScheme           = 'https';
        $url                     = 'https://qwant.com';
        $mauticUrl               = 'https://mautic.org';
        $exceptionMessage        = 'Exception!';
        $expectedResponseContent = [
            'status'       => 0,
            'errorMessage' => $exceptionMessage,
        ];

        $this->translator->expects($this->never())
            ->method('trans');

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(Request::METHOD_GET, $url)
            ->willThrowException(new \Exception($exceptionMessage));

        $this->parametersHelper->expects($this->once())
            ->method('get')
            ->with('site_url')
            ->willReturn($mauticUrl);

        $response = $this->helper->check($url, $currentScheme);

        $responseBody = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        Assert::assertSame($expectedResponseContent, $responseBody);
    }

    public function testCheckXFrameOptions(): void
    {
        $currentScheme           = 'https';
        $url                     = 'https://qwant.com';
        $mauticUrl               = 'https://mautic.org';
        $translatedErrorMessage  = 'error';
        $expectedResponseContent = [
            'status'       => 0,
            'errorMessage' => $translatedErrorMessage,
        ];

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                'mautic.focus.blocking.iframe.header',
                [
                    '%url%'    => $url,
                    '%header%' => 'x-frame-options: SAMEORIGIN',
                ]
            )
            ->willReturn($translatedErrorMessage);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getHeaders')
            ->with(false)
            ->willReturn(['x-frame-options' => ['SAMEORIGIN']]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(Request::METHOD_GET, $url)
            ->willReturn($response);

        $this->parametersHelper->expects($this->once())
            ->method('get')
            ->with('site_url')
            ->willReturn($mauticUrl);

        $response = $this->helper->check($url, $currentScheme);

        $responseBody = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        Assert::assertSame($expectedResponseContent, $responseBody);
    }

    public function testCheckCSPFrameAncestorsError(): void
    {
        $currentScheme           = 'https';
        $url                     = 'https://qwant.com';
        $mauticUrl               = 'https://mautic.org';
        $translatedErrorMessage  = 'error';
        $expectedResponseContent = [
            'status'       => 0,
            'errorMessage' => $translatedErrorMessage,
        ];

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                'mautic.focus.blocking.iframe.header',
                [
                    '%url%'    => $url,
                    '%header%' => 'content-security-policy',
                ]
            )
            ->willReturn($translatedErrorMessage);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getHeaders')
            ->with(false)
            ->willReturn(['content-security-policy' => ["img-src 'self'; frame-ancestors https://domain.tld 'self';"]]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(Request::METHOD_GET, $url)
            ->willReturn($response);

        $this->parametersHelper->expects($this->once())
            ->method('get')
            ->with('site_url')
            ->willReturn($mauticUrl);

        $response = $this->helper->check($url, $currentScheme);

        $responseBody = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        Assert::assertSame($expectedResponseContent, $responseBody);
    }

    public function testCheckCSPFrameAncestorsErrorWhenNone(): void
    {
        $currentScheme           = 'https';
        $url                     = 'https://qwant.com';
        $mauticUrl               = 'https://mautic.org';
        $translatedErrorMessage  = 'error';
        $expectedResponseContent = [
            'status'       => 0,
            'errorMessage' => $translatedErrorMessage,
        ];

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                'mautic.focus.blocking.iframe.header',
                [
                    '%url%'    => $url,
                    '%header%' => 'content-security-policy',
                ]
            )
            ->willReturn($translatedErrorMessage);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getHeaders')
            ->with(false)
            ->willReturn(['content-security-policy' => ["img-src 'self'; frame-ancestors https://domain.tld ".$url.' '.$mauticUrl." 'self' 'none' ".$url.';']]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(Request::METHOD_GET, $url)
            ->willReturn($response);

        $this->parametersHelper->expects($this->once())
            ->method('get')
            ->with('site_url')
            ->willReturn($mauticUrl);

        $response = $this->helper->check($url, $currentScheme);

        $responseBody = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        Assert::assertSame($expectedResponseContent, $responseBody);
    }

    public function testCheckCSPFrameAncestorsOkWhenEmpty(): void
    {
        $currentScheme           = 'https';
        $mauticUrl               = 'https://mautic.org';
        $externalUrl             = 'https://qwant.com';
        $expectedResponseContent = [
            'status'       => 1,
            'errorMessage' => '',
        ];

        $this->translator->expects($this->never())
            ->method('trans');

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getHeaders')
            ->with(false)
            ->willReturn(['content-security-policy' => ["img-src 'self'; frame-ancestors ;"]]);
        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(Request::METHOD_GET, $externalUrl)
            ->willReturn($response);

        $this->parametersHelper->expects($this->once())
            ->method('get')
            ->with('site_url')
            ->willReturn($mauticUrl);

        $response = $this->helper->check($externalUrl, $currentScheme);

        $responseBody = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        Assert::assertSame($expectedResponseContent, $responseBody);
    }

    #[DataProvider('provideOkFrameAncestors')]
    public function testCheckCSPFrameAncestorsOk(string $externalUrl, string $frameAncestors): void
    {
        $currentScheme           = 'https';
        $mauticUrl               = 'https://mautic.org';
        $expectedResponseContent = [
            'status'       => 1,
            'errorMessage' => '',
        ];

        $this->translator->expects($this->never())
            ->method('trans');

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getHeaders')
            ->with(false)
            ->willReturn(['content-security-policy' => ["img-src 'self'; frame-ancestors ".$frameAncestors.';']]);
        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(Request::METHOD_GET, $externalUrl)
            ->willReturn($response);

        $this->parametersHelper->expects($this->once())
            ->method('get')
            ->with('site_url')
            ->willReturn($mauticUrl);

        $response = $this->helper->check($externalUrl, $currentScheme);

        $responseBody = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        Assert::assertSame($expectedResponseContent, $responseBody);
    }

    public static function provideOkFrameAncestors(): \Generator
    {
        yield "'self' and hosted on Mautic instance" => ['https://mautic.org', "https://domain.tld 'self'"];
        yield "First 'self' and hosted on Mautic instance" => ['https://mautic.org', "'self' https://domain.tld"];
        yield 'https is allowed' => ['https://external.tld', "https://domain.tld 'self' https:"];
        yield "First 'self' https is allowed" => ['https://external.tld', "'self' https://domain.tld https:"];
        yield 'http is allowed on https' => ['https://external.tld', "https://domain.tld 'self' http:"];
        yield "First 'self' http is allowed on https" => ['https://external.tld', "'self' https://domain.tld http:"];
        yield 'Exact URL' => ['https://external.tld', "https://domain.tld 'self' https://external.tld"];
        yield "First 'self' exact URL" => ['https://external.tld', "'self' https://domain.tld https://external.tld"];
        yield 'Wildcard URL' => ['https://external.tld', "https://domain.tld 'self' https://*.external.tld"];
        yield "First 'self' wildcard URL" => ['https://external.tld', "'self' https://domain.tld https://*.external.tld"];
    }
}
