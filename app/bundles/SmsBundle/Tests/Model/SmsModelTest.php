<?php

namespace Mautic\SmsBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Mautic\ChannelBundle\Model\MessageQueueModel;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Entity\SmsRepository;
use Mautic\SmsBundle\Form\Type\SmsType;
use Mautic\SmsBundle\Model\SmsModel;
use Mautic\SmsBundle\Sms\TransportChain;

class SmsModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CacheStorageHelper
     */
    private $cacheStorageHelper;

    /**
     * @var EntityManager
     */
    private $entityManger;

    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var MessageQueueModel
     */
    private $messageQueueModel;

    /**
     * @var TrackableModel
     */
    private $pageTrackableModel;

    /**
     * @var TransportChain
     */
    private $transport;

    protected function setUp(): void
    {
        $this->pageTrackableModel = $this->createMock(TrackableModel::class);
        $this->leadModel          = $this->createMock(LeadModel::class);
        $this->messageQueueModel  = $this->createMock(MessageQueueModel::class);
        $this->transport          = $this->createMock(TransportChain::class);
        $this->cacheStorageHelper = $this->createMock(CacheStorageHelper::class);
        $this->entityManger       = $this->createMock(EntityManager::class);
    }

    /**
     * Test to get lookup results when class name is sent as a parameter.
     */
    public function testGetLookupResultsWhenTypeIsClass()
    {
        $entities       = [['name' => 'Mautic', 'id' => 1, 'language' => 'cs']];
        $repositoryMock = $this->createMock(SmsRepository::class);
        $repositoryMock->method('getSmsList')
            ->with('', 10, 0, true, false)
            ->willReturn($entities);
        // Partial mock, mocks just getRepository
        $smsModel = $this->getMockBuilder(SmsModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRepository'])
            ->getMock();
        $smsModel->method('getRepository')
            ->willReturn($repositoryMock);
        $securityMock = $this->createMock(CorePermissions::class);
        $securityMock->method('isGranted')
            ->with('sms:smses:viewother')
            ->willReturn(true);
        $smsModel->setSecurity($securityMock);
        $textMessages = $smsModel->getLookupResults(SmsType::class);
        $this->assertSame('Mautic', $textMessages['cs'][1], 'Mautic is the right text message name');
    }

    public function testSendSmsNotPublished()
    {
        $sms = new Sms();
        $sms->setIsPublished(false);
        $lead = new Lead();
        $lead->setId(1);
        $smsModel = $this->getSmsModel();
        $smsModel->setEntityManager($this->entityManger);
        $results = $smsModel->sendSms($sms, $lead);
        self::assertFalse((bool) $results[1]['sent']);
        self::assertSame('mautic.sms.campaign.failed.unpublished', $results[1]['status']);
    }

    public function getSmsModel(): SmsModel
    {
        return new SmsModel(
            $this->pageTrackableModel, $this->leadModel,
            $this->messageQueueModel, $this->transport, $this->cacheStorageHelper
        );
    }
}
