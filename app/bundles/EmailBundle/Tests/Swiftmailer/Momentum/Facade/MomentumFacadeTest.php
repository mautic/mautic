<?php

namespace Mautic\EmailBundle\Tests\Swiftmailer\Momentum\Facade;

use Mautic\EmailBundle\Swiftmailer\Momentum\Adapter\AdapterInterface;
use Mautic\EmailBundle\Swiftmailer\Momentum\Callback\MomentumCallbackInterface;
use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO;
use Mautic\EmailBundle\Swiftmailer\Momentum\Exception\Facade\MomentumSendException;
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
class MomentumFacadeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $adapterMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $swiftMessageServiceMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $swiftMessageValidatorMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $momentumCallbackMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
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
        $swiftMessageMock = $this->createMock(\Swift_Mime_SimpleMessage::class);
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
        $sparkPostPromiseMock->expects($this->once())
            ->method('wait')
            ->willReturn($sparkPostResponseMock);
        $sparkPostResponseMock->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn('200');
        $totalRecipients = 0;
        $bodyResults     = [
            'results' => [
                'total_accepted_recipients' => $totalRecipients,
            ],
        ];
        $sparkPostResponseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($bodyResults);
        $this->momentumCallbackMock->expects($this->once())
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
        $swiftMessageMock                    = $this->createMock(\Swift_Mime_SimpleMessage::class);
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
        $this->expectException(MomentumSendException::class);
        $facade->send($swiftMessageMock);
    }

    /**
     * Test for correct handle of first 500 error followed by 200.
     */
    public function testSend500FirstAttempt()
    {
        $swiftMessageMock    = $this->createMock(\Swift_Mime_SimpleMessage::class);
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
        $sparkPostResponseMock500 = $this->createMock(SparkPostResponse::class);
        $sparkPostPromiseMock->expects($this->at(0))
            ->method('wait')
            ->willReturn($sparkPostResponseMock500);
        $sparkPostResponseMock500->expects($this->once())
            ->method('getStatusCode')
            ->willReturn('500');
        $this->adapterMock->expects($this->at(1))
            ->method('createTransmission')
            ->with($transmissionDTOMock)
            ->willReturn($sparkPostPromiseMock);
        $sparkPostResponseMock200 = $this->createMock(SparkPostResponse::class);
        $sparkPostPromiseMock->expects($this->at(1))
            ->method('wait')
            ->willReturn($sparkPostResponseMock200);
        $sparkPostResponseMock200->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn('200');
        $totalRecipients = 0;
        $bodyResults     = [
            'results' => [
                'total_accepted_recipients' => $totalRecipients,
            ],
        ];
        $sparkPostResponseMock200->expects($this->once())
            ->method('getBody')
            ->willReturn($bodyResults);
        $this->momentumCallbackMock->expects($this->at(0))
            ->method('processImmediateFeedback')
            ->with($swiftMessageMock, $bodyResults);
        $facade = $this->getMomentumFacade();
        $this->assertSame($totalRecipients, $facade->send($swiftMessageMock));
    }

    /**
     * Test for correct handle of repeated 500s.
     */
    public function testSend500Repeated()
    {
        $swiftMessageMock    = $this->createMock(\Swift_Mime_SimpleMessage::class);
        $transmissionDTOMock = $this->createMock(TransmissionDTO::class);
        $this->swiftMessageServiceMock->expects($this->at(0))
            ->method('transformToTransmission')
            ->with($swiftMessageMock)
            ->willReturn($transmissionDTOMock);
        $sparkPostPromiseMock = $this->createMock(SparkPostPromise::class);
        $this->adapterMock->expects($this->exactly(3))
            ->method('createTransmission')
            ->with($transmissionDTOMock)
            ->willReturn($sparkPostPromiseMock);

        $sparkPostResponseMock1 = $this->createMock(SparkPostResponse::class);
        $sparkPostResponseMock1->expects($this->once())
            ->method('getStatusCode')
            ->willReturn('500');
        $sparkPostPromiseMock->expects($this->at(0))
            ->method('wait')
            ->willReturn($sparkPostResponseMock1);

        $sparkPostResponseMock2 = $this->createMock(SparkPostResponse::class);
        $sparkPostResponseMock2->expects($this->once())
            ->method('getStatusCode')
            ->willReturn('500');
        $sparkPostPromiseMock->expects($this->at(1))
            ->method('wait')
            ->willReturn($sparkPostResponseMock2);

        $sparkPostResponseMock3 = $this->createMock(SparkPostResponse::class);
        $sparkPostResponseMock3->expects($this->exactly(3))
            ->method('getStatusCode')
            ->willReturn('500');
        $responseBody = 'Empty';
        $sparkPostResponseMock3->expects($this->exactly(2))
            ->method('getBody')
            ->willReturn($responseBody);
        $sparkPostPromiseMock->expects($this->at(2))
            ->method('wait')
            ->willReturn($sparkPostResponseMock3);

        $this->loggerMock->expects($this->at(0))
            ->method('addError')
            ->with('Momentum send: 500', [
                'response' => $responseBody,
            ]);

        $this->loggerMock->expects($this->at(1))
            ->method('addError')
            ->with('Momentum send exception', [
                'message' => $responseBody,
            ]);

        $facade = $this->getMomentumFacade();
        $this->expectException(MomentumSendException::class);
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
