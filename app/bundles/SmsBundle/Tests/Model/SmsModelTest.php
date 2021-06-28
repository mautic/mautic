<?php

namespace Mautic\SmsBundle\Tests\Model;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\SmsBundle\Entity\SmsRepository;
use Mautic\SmsBundle\Form\Type\SmsType;
use Mautic\SmsBundle\Model\SmsModel;

class SmsModelTest extends \PHPUnit\Framework\TestCase
{
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
}
