<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Model;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Tests\PageTestAbstract;
use Symfony\Component\HttpFoundation\Request;

final class PageModelTest extends PageTestAbstract
{
    public function testUtf8CharsInTitleWithTransletirationEnabled(): void
    {
        $providedTitle = '你好，世界';
        $expectedTitle = 'ni hao, shi jie';
        $hit           = new Hit();
        $page          = new Page();
        $request       = new Request();
        $contact       = new Lead();
        $pageModel     = $this->getPageModel();

        $hit->setIpAddress(new IpAddress());
        $hit->setQuery(['page_title' => $providedTitle]);

        $pageModel->processPageHit($hit, $page, $request, $contact, false);

        $this->assertSame($expectedTitle, $hit->getUrlTitle());
        $this->assertSame(['page_title' => $expectedTitle], $hit->getQuery());
    }

    public function testUtf8CharsInTitleWithTransletirationDisabled(): void
    {
        $providedTitle = '你好，世界';
        $expectedTitle = '你好，世界';
        $hit           = new Hit();
        $page          = new Page();
        $request       = new Request();
        $contact       = new Lead();
        $pageModel     = $this->getPageModel(false);

        $hit->setIpAddress(new IpAddress());
        $hit->setQuery(['page_title' => $providedTitle]);

        $pageModel->processPageHit($hit, $page, $request, $contact, false);

        $this->assertSame($expectedTitle, $hit->getUrlTitle());
        $this->assertSame(['page_title' => $expectedTitle], $hit->getQuery());
    }

    public function testGenerateUrlWhenCalledReturnsValidUrl(): void
    {
        $page = new Page();
        $page->setAlias('this-is-a-test');
        $pageModel = $this->getPageModel();

        $this->router->expects($this->once())
            ->method('generate')
            ->willReturnCallback(
                function (string $route, array $routeParams, int $referenceType): string {
                    $this->assertSame('mautic_page_public', $route);
                    $this->assertSame(['slug' => 'this-is-a-test'], $routeParams);
                    $this->assertSame(0, $referenceType);

                    return '/'.$routeParams['slug'];
                }
            );

        $url = $pageModel->generateUrl($page);
        $this->assertStringContainsString('/this-is-a-test', $url);
    }

    public function testUrlTitleFallbacksToPageTitleWhenNotInQuery(): void
    {
        $providedTitle = '你好，世界';
        $expectedTitle = 'ni hao, shi jie';
        $hit           = new Hit();
        $page          = new Page();
        $request       = new Request();
        $contact       = new Lead();
        $ipAddress     = new IpAddress();
        $pageModel     = $this->getPageModel();

        $page->setTitle($providedTitle);
        $hit->setIpAddress($ipAddress);
        $hit->setQuery([]);

        $pageModel->processPageHit($hit, $page, $request, $contact, false);

        $this->assertSame($expectedTitle, $hit->getUrlTitle());
    }

    public function testCleanQueryWhenCalledReturnsSafeAndValidData(): void
    {
        $pageModel           = $this->getPageModel();
        $pageModelReflection = new \ReflectionClass($pageModel::class);
        $cleanQueryMethod    = $pageModelReflection->getMethod('cleanQuery');
        $res                 = $cleanQueryMethod->invokeArgs($pageModel, [
            [
                'page_title'    => 'Mautic & PHP',
                'page_url'      => 'http://mautic.com/page/test?hello=world&lorem=ipsum&q=this%20has%20spaces',
                'page_language' => 'en',
            ],
        ]);
        $this->assertEquals($res, [
            'page_title'    => 'Mautic &#38; PHP',
            'page_url'      => 'http://mautic.com/page/test?hello=world&lorem=ipsum&q=this%20has%20spaces',
            'page_language' => 'en',
        ]);
    }

    /**
     * Regression test for PR #15985: a byte that is not valid standalone UTF-8
     * (e.g. \xAD, a raw "soft hyphen" pasted from Word content) must not survive
     * cleanQuery(), because $query is persisted to the page_hits.query column - a
     * Doctrine "array"-typed column serialized via serialize() with no UTF-8
     * validation of its own. If an invalid byte reaches that INSERT, MySQL rejects
     * it under STRICT_TRANS_TABLES with "SQLSTATE 1366 Incorrect string value".
     * The `ct` case covers the clickthrough payload, which getHitQuery() decodes
     * into a nested array before cleanQuery() runs.
     *
     * @param array<string, mixed> $query
     * @param array<int, string>   $path  nested keys to the string leaf under test
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provideInvalidUtf8QueryPayloads')]
    public function testCleanQueryStripsInvalidUtf8Bytes(array $query, array $path): void
    {
        $pageModel           = $this->getPageModel();
        $pageModelReflection = new \ReflectionClass($pageModel::class);
        $cleanQueryMethod    = $pageModelReflection->getMethod('cleanQuery');
        $result              = $cleanQueryMethod->invokeArgs($pageModel, [$query]);

        $value = $result;
        foreach ($path as $segment) {
            $this->assertIsArray($value, sprintf('Expected an array at query path up to "%s"', $segment));
            $this->assertArrayHasKey($segment, $value, sprintf('Missing expected key "%s"', $segment));
            $value = $value[$segment];
        }

        $this->assertIsString($value);
        $this->assertTrue(
            mb_check_encoding($value, 'UTF-8'),
            sprintf(
                'Value at query path [%s] is not valid UTF-8 (bytes: %s) - an invalid byte here '.
                'will crash the page_hits.query INSERT under STRICT_TRANS_TABLES (SQLSTATE 1366).',
                implode('.', $path),
                bin2hex($value)
            )
        );
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>, 1: array<int, string>}>
     */
    public static function provideInvalidUtf8QueryPayloads(): iterable
    {
        // \xAD is a lone "soft hyphen" byte: >0x7F so not plain ASCII, and not a valid
        // UTF-8 lead/continuation byte on its own, so mb_check_encoding() rejects it.
        $invalidUtf8 = "before\xADafter";

        yield 'invalid byte in a top-level string param' => [
            [
                'page_title' => $invalidUtf8,
                'page_url'   => 'https://example.com/page',
            ],
            ['page_title'],
        ];

        yield 'invalid byte nested inside the ct clickthrough array param' => [
            [
                'page_title' => 'Testpage',
                'ct'         => [
                    'source' => $invalidUtf8,
                ],
            ],
            ['ct', 'source'],
        ];
    }

    /**
     * Test getHitQuery when the hit is a Request
     * (e.g. POST Ajax or Landingpage hit).
     */
    public function testGetHitQueryRequest(): void
    {
        $pageModel         = $this->getPageModel();

        foreach ($this->getQueryParams() as $params) {
            $request = new Request($params);

            $query = $pageModel->getHitQuery($request);
            $this->assertUtmQuery($query);
        }
    }

    public function testTimezoneQueryProcessPageHit(): void
    {
        $hit           = new Hit();
        $page          = new Page();
        $request       = new Request();
        $contact       = new Lead();
        $pageModel     = $this->getPageModel(false);

        $hit->setIpAddress(new IpAddress());
        $timezone = 'Europe/Paris';
        $hit->setQuery(['timezone' => $timezone, 'timezone_offset' => -120]);

        $pageModel->processPageHit($hit, $page, $request, $contact, false);
        $this->assertSame($timezone, $contact->getTimezone());

        $hit->setQuery(['timezone_offset' => -120]);

        $contact       = new Lead();
        $pageModel->processPageHit($hit, $page, $request, $contact, false);

        $this->assertSame('Europe/Helsinki', $contact->getTimezone());
    }

    /**
     * Test getHitQuery when the hit is a Redirect.
     */
    public function testGetHitQueryRedirect(): void
    {
        $pageModel         = $this->getPageModel();
        $request           = new Request();
        $redirect          = new Redirect();

        foreach ($this->getQueryParams() as $params) {
            $redirect->setUrl($params['page_url']);
            $query = $pageModel->getHitQuery($request, $redirect);
            $this->assertUtmQuery($query);
        }
    }

    /**
     * This test is somewhat synthetic to test the missing $query['ct'].
     */
    public function testNoClickThroughInQuery(): void
    {
        $redirectUrl = '/somewhat';
        $pageModel   = $this->getPageModel();

        $ipAddress = $this->createMock(IpAddress::class);
        $ipAddress->method('isTrackable')->willReturn(true);

        $this->security->method('isAnonymous')->willReturn(true);
        $this->ipLookupHelper->method('getIpAddress')->willReturn($ipAddress);
        $this->companyModel->method('fetchCompanyFields')->willReturn([]);

        $redirect = $this->createMock(Redirect::class);
        $redirect->method('getUrl')->willReturn($redirectUrl);

        $this->contactRequestHelper->expects($this->once())
            ->method('getContactFromQuery')
            ->with(['page_url' => $redirectUrl])
            ->willReturn(null);

        $result = $pageModel->hitPage($redirect, new Request());
        $this->assertFalse($result);
    }

    /**
     * @param array<string, string> $query
     */
    private function assertUtmQuery(array $query): void
    {
        $this->assertArrayHasKey('utm_source', $query, 'utm_source not found');
        $this->assertArrayHasKey('utm_medium', $query, 'utm_medium not found');
        $this->assertArrayHasKey('utm_campaign', $query, 'utm_campaign not found');
        $this->assertArrayHasKey('utm_content', $query, 'utm_content not found');
        // evaluate all utm tags that they contain the key name in the value
        foreach ($query as $key => $value) {
            if (str_contains($key, 'utm_')) {
                $this->assertStringContainsString($key, $value, sprintf('%s not found in %s', $key, $value));
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getQueryParams(): array
    {
        $utm = [
            'utm_source'  => 'test-utm_source',
            'utm_medium'  => 'test-utm_medium',
            'utm_campaign'=> 'test-utm_campaign',
            'utm_content' => 'test-utm_content',
        ];
        $querystring = '';
        foreach ($utm as $key => $value) {
            $querystring .= sprintf('&%s=%s', $key, $value);
        }

        $ctParams = [
            'source'  => ['email', '4'],
            'email'   => 4,
            'stat'    => '5f5dedc3b0dc0366144010',
            'lead'    => 2,
            'channel' => [
                'email' => 4,
            ],
        ];
        $ct      = ClickthroughHelper::encodeArrayForUrl($ctParams);

        return [[
            'page_title'      => 'Testpage',
            'page_language'   => 'en-GB',
            'page_referrer'   => '',
            'page_url'        => sprintf('https://www.domain.com/testpage/?%s', $querystring),
            'counter'         => 0,
            'mautic_device_id'=> 'nowvkqdf6113236eokcg7qs',
            'resolution'      => '1792x1120',
            'timezone_offset' => -120,
            'platform'        => 'MacIntel',
            'do_not_track'    => 1,
            'adblock'         => false,
            'fingerprint'     => 'fec25ab2d659c4153c7f1d5724841132',
        ], [
            'page_title'      => 'Testpage Special Chars',
            'page_language'   => 'en-GB',
            'page_referrer'   => '',
            'page_url'        => 'https://www.domain.com/testpage/?utm_source=t%C3%A9%C3%A0%C3%A8st-utm_source&utm_medium=t%C3%A4%C3%B6ust-utm_medium&utm_campaign=te+%20%C2%B0st-utm_campaign&utm_content=t%E4%BD%A0%E5%A5%BDt-utm_content',
            'counter'         => 0,
            'mautic_device_id'=> 'nowvkqdf6113236eokcg7qs',
            'resolution'      => '1792x1120',
            'timezone_offset' => -120,
            'platform'        => 'MacIntel',
            'do_not_track'    => 1,
            'adblock'         => false,
            'fingerprint'     => 'fec25ab2d659c4153c7f1d5724841132',
        ], [
            'page_title'      => 'Testpage With Encoded Params',
            'page_language'   => 'en-GB',
            'page_referrer'   => '',
            'page_url'        => sprintf('https://www.domain.com/testpage/?ct=%s&%s', $ct, $querystring),
            'counter'         => 0,
            'mautic_device_id'=> 'nowvkqdf6113236eokcg7qs',
            'resolution'      => '1792x1120',
            'timezone_offset' => -120,
            'platform'        => 'MacIntel',
            'do_not_track'    => 1,
            'adblock'         => false,
            'fingerprint'     => 'fec25ab2d659c4153c7f1d5724841132',
        ]];
    }
}
