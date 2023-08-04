<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Test\Doctrine\DBALMocker;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\LeadBundle\Tracker\DeviceTracker;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\Model\RedirectModel;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\QueueBundle\Queue\QueueService;
use PHPUnit\Framework\TestCase;

class PageModelUnitTest extends TestCase
{
    public const POPULAR_TRACKED_PAGES_QUERY_RESPONSE = [
        [
            'url_title' => 'Page 1',
            'url'       => 'https://domain1.tld',
            'hits'      => 2,
        ],
        [
            'url_title' => 'Page 2',
            'url'       => 'https://domain2.tld',
            'hits'      => 1,
        ],
    ];

    /**
     * @var PageModel|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $pageModel;

    /**
     * @var EntityManager|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityManager;

    public function setUp(): void
    {
        $this->entityManager            = $this->createMock(EntityManager::class);
        $cookieHelper                   = $this->createMock(CookieHelper::class);
        $ipLookupHelper                 = $this->createMock(IpLookupHelper::class);
        $leadModel                      = $this->createMock(LeadModel::class);
        $leadFieldModel                 = $this->createMock(FieldModel::class);
        $pageRedirectModel              = $this->createMock(RedirectModel::class);
        $pageTrackableModel             = $this->createMock(TrackableModel::class);
        $queueService                   = $this->createMock(QueueService::class);
        $companyModel                   = $this->createMock(CompanyModel::class);
        $deviceTracker                  = $this->createMock(DeviceTracker::class);
        $contactTracker                 = $this->createMock(ContactTracker::class);
        $coreParametersHelper           = $this->createMock(CoreParametersHelper::class);

        $this->pageModel      = new PageModel(
            $cookieHelper,
            $ipLookupHelper,
            $leadModel,
            $leadFieldModel,
            $pageRedirectModel,
            $pageTrackableModel,
            $queueService,
            $companyModel,
            $deviceTracker,
            $contactTracker,
            $coreParametersHelper);
    }

    public function testGetBestHours()
    {
        $dbalMock      = new DBALMocker($this);
        $dbalMock->setQueryResponse(
            self::POPULAR_TRACKED_PAGES_QUERY_RESPONSE
        );
        $mockConnection = $dbalMock->getMockConnection();

        $this->entityManager->method('getConnection')->willReturn($mockConnection);
        $this->pageModel->setEntityManager($this->entityManager);

        $chartData = $this->pageModel->getPopularTrackedPages(
            10,
            new \DateTime(),
            new \DateTime()
        );

        $this->assertSame($chartData, self::POPULAR_TRACKED_PAGES_QUERY_RESPONSE);
    }
}
