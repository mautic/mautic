<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Tests;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\UrlHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Entity\PageRepository;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\Model\RedirectModel;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\PageBundle\Model\VideoModel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class PageTestAbstract extends WebTestCase
{
    protected static $mockId   = 123;
    protected static $mockName = 'Mock test name';
    protected $mockTrackingId;
    protected $container;

    protected function setUp()
    {
        self::bootKernel();
        $this->mockTrackingId = hash('sha1', uniqid(mt_rand(), true));
        $this->container      = self::$kernel->getContainer();
    }

    /**
     * @return PageModel
     */
    protected function getPageModel()
    {
        $cookieHelper = $this
            ->getMockBuilder(CookieHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $ipLookupHelper = $this
            ->getMockBuilder(IpLookupHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $leadModel = $this
            ->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $leadFieldModel = $this
            ->getMockBuilder(FieldModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $redirectModel = $this->getRedirectModel();

        $trackableModel = $this
            ->getMockBuilder(TrackableModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dispatcher = $this
            ->getMockBuilder(EventDispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();

        $translator = $this
            ->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $leadModel->expects($this
            ->any())
            ->method('getCurrentLead')
            ->willReturn($this
                ->returnValue(['id' => self::$mockId, 'name' => self::$mockName]));

        $entityManager = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pageRepository = $this
            ->getMockBuilder(PageRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this
            ->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['MauticPageBundle:Page', $pageRepository],
                    ]
                )
            );

        $pageModel = new PageModel(
            $cookieHelper,
            $ipLookupHelper,
            $leadModel,
            $leadFieldModel,
            $redirectModel,
            $trackableModel
        );

        $pageModel->setDispatcher($dispatcher);
        $pageModel->setTranslator($translator);
        $pageModel->setEntityManager($entityManager);

        return $pageModel;
    }

    public function getCurrentLead($tracking)
    {
        return $tracking ? [new Lead(), $this->mockTrackingId, true] : new Lead();
    }

    /**
     * @return RedirectModel
     */
    protected function getRedirectModel()
    {
        $urlHelper = $this
            ->getMockBuilder(UrlHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockRedirectModel = $this->getMockBuilder('Mautic\PageBundle\Model\RedirectModel')
            ->setConstructorArgs([$urlHelper])
            ->setMethods(['createRedirectEntity', 'generateRedirectUrl'])
            ->getMock();

        $mockRedirect = $this->getMockBuilder('Mautic\PageBundle\Entity\Redirect')
            ->getMock();

        $mockRedirectModel->expects($this->any())
            ->method('createRedirectEntity')
            ->willReturn($mockRedirect);

        $mockRedirectModel->expects($this->any())
            ->method('generateRedirectUrl')
            ->willReturn('http://some-url.com');

        return $mockRedirectModel;
    }

    /**
     * @return VideoModel
     */
    protected function getVideoModel()
    {
        $ipLookupHelper = $this
            ->getMockBuilder(IpLookupHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $leadModel = $this
            ->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockRedirectModel = $this->getMockBuilder('Mautic\PageBundle\Model\VideoModel')
            ->setConstructorArgs([$leadModel, $ipLookupHelper])
            ->getMock();

        return $mockRedirectModel;
    }
}
