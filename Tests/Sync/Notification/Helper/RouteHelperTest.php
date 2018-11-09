<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Sync\Notification\Helper;


use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException;
use MauticPlugin\IntegrationsBundle\Sync\Notification\Helper\RouteHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Symfony\Component\Routing\Router;

class RouteHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Router|\PHPUnit_Framework_MockObject_MockObject
     */
    private $router;

    protected function setUp()
    {
        $this->router = $this->createMock(Router::class);
    }

   public function testContactRoute()
   {
       $this->router->method('generate')
           ->willReturnCallback(
               function (string $name, array $params) {
                   $this->assertEquals('mautic_contact_action', $name);

                   return 'foo.bar';
               }
           );

        $this->getRouteHelper()->getRoute(MauticSyncDataExchange::OBJECT_CONTACT, 1);
   }

    public function testCompanyRoute()
    {
        $this->router->method('generate')
            ->willReturnCallback(
                function (string $name, array $params) {
                    $this->assertEquals('mautic_company_action', $name);

                    return 'foo.bar';
                }
            );
    }

    public function testExceptionThrownWithUnsupportedObject()
    {
        $this->expectException(ObjectNotSupportedException::class);

        $this->getRouteHelper()->getRoute('FooBar', 1);
    }


    public function testLink()
    {
        $this->router->method('generate')
            ->willReturnCallback(
                function (string $name, array $params) {
                    $this->assertEquals('mautic_contact_action', $name);

                    return 'foo.bar';
                }
            );

        $link = $this->getRouteHelper()->getLink(MauticSyncDataExchange::OBJECT_CONTACT, 1, 'Hello');
        $this->assertEquals('<a href="foo.bar">Hello</a>', $link);
    }

    public function testLinkCsv()
    {
        $this->router->method('generate')
            ->willReturnCallback(
                function (string $name, array $params) {
                    $this->assertEquals('mautic_contact_action', $name);

                    return 'foo.bar';
                }
            );

        $csv = $this->getRouteHelper()->getLinkCsv(MauticSyncDataExchange::OBJECT_CONTACT, [1,2]);
        $this->assertEquals('[<a href="foo.bar">1</a>], [<a href="foo.bar">2</a>]', $csv);
    }

    /**
     * @return RouteHelper
     */
    private function getRouteHelper()
    {
        return new RouteHelper($this->router);
    }
}