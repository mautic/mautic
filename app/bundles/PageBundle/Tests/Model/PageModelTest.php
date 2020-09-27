<?php

declare(strict_types=1);
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @see        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Tests\Model;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Tests\PageTestAbstract;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;

class PageModelTest extends PageTestAbstract
{
    public function testUtf8CharsInTitle()
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

    public function testGenerateUrl_WhenCalled_ReturnsValidUrl()
    {
        $page = new Page();
        $page->setAlias('this-is-a-test');
        $pageModel = $this->getPageModel();
        $url       = $pageModel->generateUrl($page);
        $this->assertContains('/this-is-a-test', $url);
    }

    public function testCleanQuery_WhenCalled_ReturnsSafeAndValidData()
    {
        $pageModel           = $this->getPageModel();
        $pageModelReflection = new ReflectionClass(get_class($pageModel));
        $cleanQueryMethod    = $pageModelReflection->getMethod('cleanQuery');
        $cleanQueryMethod->setAccessible(true);
        $res = $cleanQueryMethod->invokeArgs($pageModel, [
            [
                'page_title'    => 'Mautic & PHP',
                'page_url'      => 'http://mautic.com/page/test?hello=world&lorem=ipsum',
                'page_language' => 'en',
            ],
        ]);
        $this->assertEquals($res, [
            'page_title'    => 'Mautic &#38; PHP',
            'page_url'      => 'http://mautic.com/page/test?hello=world&lorem=ipsum',
            'page_language' => 'en',
        ]);
    }

    public function testGetHitQueryRequest()
    {
        $pageModel         = $this->getPageModel();

        foreach ($this->getQueryParams() as $params) {
            $request = new Request($params);

            $query = $pageModel->getHitQuery($request);
            $this->assertQuery($query);
        }
    }

    public function testGetHitQueryRedirect()
    {
        $pageModel         = $this->getPageModel();
        $request           = new Request();
        $redirect          = new Redirect();

        foreach ($this->getQueryParams() as $params) {
            $redirect->setUrl($params['page_url']);
            $query = $pageModel->getHitQuery($request, $redirect);
            $this->assertQuery($query);
        }
    }

    private function assertQuery(array $query)
    {
        $this->assertArrayHasKey('utm_source', $query, 'utm_source not found');
        $this->assertArrayHasKey('utm_medium', $query, 'utm_medium not found');
        $this->assertArrayHasKey('utm_campaign', $query, 'utm_campaign not found');
        $this->assertArrayHasKey('utm_content', $query, 'utm_content not found');
        // evaluate all utm tags that they contain the key name in the value
        foreach ($query as $key => $value) {
            if (false !== strpos($key, 'utm_')) {
                $this->assertNotFalse(strpos($value, $key), sprintf('%s not found in %s', $key, $value));
            }
        }
    }

    private function getQueryParams()
    {
        return [[
            'page_title'      => 'Testpage',
            'page_language'   => 'en-GB',
            'page_referrer'   => '',
            'page_url'        => 'https://www.domain.com/testpage/?utm_source=test-utm_source&utm_medium=test-utm_medium&utm_campaign=test-utm_campaign&utm_content=test-utm_content',
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
        ]];
    }
}
