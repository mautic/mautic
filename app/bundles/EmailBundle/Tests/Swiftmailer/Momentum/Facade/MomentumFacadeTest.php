<?php

namespace Mautic\EmailBundle\Tests\Swiftmailer\Momentum\Facade;

use Mautic\EmailBundle\Swiftmailer\Momentum\Adapter\AdapterInterface;
use Mautic\EmailBundle\Swiftmailer\Momentum\Callback\MomentumCallbackInterface;
use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO;
use Mautic\EmailBundle\Swiftmailer\Momentum\Exception\Validator\SwiftMessageValidator\SwiftMessageValidationException;
use Mautic\EmailBundle\Swiftmailer\Momentum\Facade\MomentumFacade;
use Mautic\EmailBundle\Swiftmailer\Momentum\Service\SwiftMessageServiceInterface;
use Mautic\EmailBundle\Swiftmailer\Momentum\Validator\SwiftMessageValidator\SwiftMessageValidatorInterface;
use Monolog\Logger;
use SparkPost\SparkPostPromise;
use SparkPost\SparkPostResponse;

/**
 * Class MomentumFacadeTest.
 */
class MomentumFacadeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $adapterMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $swiftMessageServiceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $swiftMessageValidatorMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $momentumCallbackMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    protected function setUp()
    {
        parent::setUp();
        $this->adapterMock               = $this->createMock(AdapterInterface::class);
        $this->swiftMessageServiceMock   = $this->createMock(SwiftMessageServiceInterface::class);
        $this->swiftMessageValidatorMock = $this->createMock(SwiftMessageValidatorInterface::class);
        $this->momentumCallbackMock      = $this->createMock(MomentumCallbackInterface::class);
        $this->loggerMock                = $this->createMock(Logger::class);
    }

    public function testSendOk()
    {
        $swiftMessageMock = $this->createMock(\Swift_Mime_Message::class);
        $this->swiftMessageValidatorMock->expects($this->at(0))
            ->method('validate')
            ->with($swiftMessageMock);
        $transmissionDTOMock = $this->createMock(TransmissionDTO::class);
        $this->swiftMessageServiceMock->expects($this->at(0))
            ->method('transformToTransmission')
            ->with($swiftMessageMock)
            ->willReturn($transmissionDTOMock);
        $sparkPostPromiseMock = $this->createMock(SparkPostPromise::class);
        $this->adapterMock->expects($this->at(0))
            ->method('createTransmission')
            ->with($transmissionDTOMock)
            ->willReturn($sparkPostPromiseMock);
        $sparkPostResponseMock = $this->createMock(SparkPostResponse::class);
        $sparkPostPromiseMock->expects($this->at(0))
            ->method('wait')
            ->willReturn($sparkPostResponseMock);
        $sparkPostResponseMock->expects($this->at(0))
            ->method('getStatusCode')
            ->willReturn('200');
        $totalRecipients = 0;
        $bodyResults     = [
            'results' => [
                'total_accepted_recipients' => $totalRecipients,
            ],
        ];
        $sparkPostResponseMock->expects($this->at(1))
            ->method('getBody')
            ->willReturn($bodyResults);
        $this->momentumCallbackMock->expects($this->at(0))
            ->method('processImmediateFeedback')
            ->with($swiftMessageMock, $bodyResults);
        $facade = $this->getMomentumFacade();
        $this->assertSame($totalRecipients, $facade->send($swiftMessageMock));
    }

    /**
     * Test for SwiftMessageValidationException exception.
     */
    public function testSendValidatorError()
    {
        $swiftMessageMock                    = $this->createMock(\Swift_Mime_Message::class);
        $exceptionMessage                    = 'Example exception message';
        $swiftMessageValidationExceptionMock = new SwiftMessageValidationException($exceptionMessage);
        $this->swiftMessageValidatorMock->expects($this->at(0))
            ->method('validate')
            ->with($swiftMessageMock)
            ->willThrowException($swiftMessageValidationExceptionMock);
        $this->loggerMock->expects($this->at(0))
            ->method('addError')
            ->with('Momentum send exception', [
                'message' => $exceptionMessage,
            ]);
        $facade = $this->getMomentumFacade();
        $this->expectException(SwiftMessageValidationException::class);
        $facade->send($swiftMessageMock);
    }

    /**
     * @return MomentumFacade
     */
    private function getMomentumFacade()
    {
        return new MomentumFacade(
            $this->adapterMock,
            $this->swiftMessageServiceMock,
            $this->swiftMessageValidatorMock,
            $this->momentumCallbackMock,
            $this->loggerMock
        );
    }
}
