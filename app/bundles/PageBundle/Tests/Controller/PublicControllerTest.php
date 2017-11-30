<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Templating\Helper\AnalyticsHelper;
use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Model\PageModel;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;

class PublicControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test that the appropriate variant is displayed based on hit counts and variant weights.
     */
    public function testVariantPageWeightsAreAppropriate()
    {
        // Each of these should return the one with the greatest weight deficit based on
        // A = 50%
        // B = 25%
        // C = 25%

        // A = 0/50; B = 0/25; C = 0/25
        $this->assertEquals('pageA', $this->getVariantContent(0, 0, 0));

        // A = 100/50; B = 0/25; C = 0/25
        $this->assertEquals('pageB', $this->getVariantContent(1, 0, 0));

        // A = 50/50; B = 50/25; C = 0/25;
        $this->assertEquals('pageC', $this->getVariantContent(1, 1, 0));

        // A = 33/50; B = 33/25; C = 33/25;
        $this->assertEquals('pageA', $this->getVariantContent(1, 1, 1));

        // A = 66/50; B = 33/25; C = 0/25
        $this->assertEquals('pageC', $this->getVariantContent(2, 1, 0));

        // A = 50/50; B = 25/25; C = 25/25
        $this->assertEquals('pageA', $this->getVariantContent(2, 1, 1));

        // A = 33/50; B = 66/50; C = 0/25
        $this->assertEquals('pageC', $this->getVariantContent(1, 2, 0));

        // A = 25/50; B = 50/50; C = 25/25
        $this->assertEquals('pageA', $this->getVariantContent(1, 2, 1));

        // A = 55/50; B = 18/25; C = 27/25
        $this->assertEquals('pageB', $this->getVariantContent(6, 2, 3));

        // A = 50/50; B = 25/25; C = 25/25
        $this->assertEquals('pageA', $this->getVariantContent(6, 3, 3));
    }

    /**
     * @param $aCount
     * @param $bCount
     * @param $cCount
     *
     * @return string
     */
    private function getVariantContent($aCount, $bCount, $cCount)
    {
        $pageEntityB = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pageEntityB->method('getId')
            ->will($this->returnValue(2));
        $pageEntityB->method('isPublished')
            ->will($this->returnValue(true));
        $pageEntityB->method('getVariantHits')
            ->will($this->returnValue($bCount));
        $pageEntityB->method('getTranslations')
            ->will($this->returnValue([]));
        $pageEntityB->method('isTranslation')
            ->will($this->returnValue(false));
        $pageEntityB->method('getContent')
            ->will($this->returnValue(null));
        $pageEntityB->method('getCustomHtml')
            ->will($this->returnValue('pageB'));
        $pageEntityB->method('getVariantSettings')
            ->will($this->returnValue(['weight' => '25']));

        $pageEntityC = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pageEntityC->method('getId')
            ->will($this->returnValue(3));
        $pageEntityC->method('isPublished')
            ->will($this->returnValue(true));
        $pageEntityC->method('getVariantHits')
            ->will($this->returnValue($cCount));
        $pageEntityC->method('getTranslations')
            ->will($this->returnValue([]));
        $pageEntityC->method('isTranslation')
            ->will($this->returnValue(false));
        $pageEntityC->method('getContent')
            ->will($this->returnValue(null));
        $pageEntityC->method('getCustomHtml')
            ->will($this->returnValue('pageC'));
        $pageEntityC->method('getVariantSettings')
            ->will($this->returnValue(['weight' => '25']));

        $pageEntityA = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pageEntityA->method('getId')
            ->will($this->returnValue(1));
        $pageEntityA->method('isPublished')
            ->will($this->returnValue(true));
        $pageEntityA->method('getVariants')
            ->will($this->returnValue([$pageEntityA, [2 => $pageEntityB, 3 => $pageEntityC]]));
        $pageEntityA->method('getVariantHits')
            ->will($this->returnValue($aCount));
        $pageEntityA->method('getTranslations')
            ->will($this->returnValue([]));
        $pageEntityA->method('isTranslation')
            ->will($this->returnValue(false));
        $pageEntityA->method('getContent')
            ->will($this->returnValue(null));
        $pageEntityA->method('getCustomHtml')
            ->will($this->returnValue('pageA'));
        $pageEntityA->method('getVariantSettings')
            ->will($this->returnValue(['weight' => '50']));

        $cookieHelper = $this->getMockBuilder(CookieHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $ipHelper = $this->getMockBuilder(IpLookupHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $ipHelper->method('getIpAddress')
            ->will($this->returnValue(new IpAddress()));

        $assetHelper = $this->getMockBuilder(AssetsHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mauticSecurity = $this->getMockBuilder(CorePermissions::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mauticSecurity->method('hasEntityAccess')
            ->will($this->returnValue(false));

        $analyticsHelper = $this->getMockBuilder(AnalyticsHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pageModel = $this->getMockBuilder(PageModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pageModel->method('getHitQuery')
            ->will($this->returnValue([]));
        $pageModel->method('getEntityBySlugs')
            ->will($this->returnValue($pageEntityA));
        $pageModel->method('hitPage')
            ->will($this->returnValue(true));

        $leadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $leadModel->method('getContactFromRequest')
            ->will($this->returnValue(new Lead()));

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dispatcher = new EventDispatcher();

        $modelFactory = $this->getMockBuilder(ModelFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $modelFactory->method('getModel')
            ->will(
                $this->returnValueMap(
                    [
                        ['page', $pageModel],
                        ['lead', $leadModel],
                    ]
                )
            );

        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $container->method('has')
            ->will($this->returnValue(true));
        $container->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['mautic.helper.cookie', Container::EXCEPTION_ON_INVALID_REFERENCE, $cookieHelper],
                        ['templating.helper.assets', Container::EXCEPTION_ON_INVALID_REFERENCE, $assetHelper],
                        ['mautic.helper.ip_lookup', Container::EXCEPTION_ON_INVALID_REFERENCE, $ipHelper],
                        ['mautic.security', Container::EXCEPTION_ON_INVALID_REFERENCE, $mauticSecurity],
                        ['mautic.helper.template.analytics', Container::EXCEPTION_ON_INVALID_REFERENCE, $analyticsHelper],
                        ['mautic.page.model.page', Container::EXCEPTION_ON_INVALID_REFERENCE, $pageModel],
                        ['mautic.lead.model.lead', Container::EXCEPTION_ON_INVALID_REFERENCE, $leadModel],
                        ['router', Container::EXCEPTION_ON_INVALID_REFERENCE, $router],
                        ['event_dispatcher', Container::EXCEPTION_ON_INVALID_REFERENCE, $dispatcher],
                        ['mautic.model.factory', Container::EXCEPTION_ON_INVALID_REFERENCE, $modelFactory],
                    ]
                )
            );

        $request = new Request();
        $request->attributes->set('ignore_mismatch', true);

        $publicController = new \Mautic\PageBundle\Controller\PublicController();
        $publicController->setContainer($container);
        $publicController->setRequest($request);

        $response = $publicController->indexAction('/page/a', $request);

        return $response->getContent();
    }
}
