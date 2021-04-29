<?php

declare(strict_types=1);

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
use PHPUnit\Framework\MockObject\MockObject;
use SparkPost\SparkPostPromise;
use SparkPost\SparkPostResponse;

/**
 * @todo this test is slow as it calls methods with sleep(5). Find a better way to speed it up.
 */
class MomentumFacadeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|AdapterInterface
     */
    private $adapterMock;

    /**
     * @var MockObject|SwiftMessageServiceInterface
     */
    private $swiftMessageServiceMock;

    /**
     * @var MockObject|SwiftMessageValidatorInterface
     */
    private $swiftMessageValidatorMock;

    /**
     * @var MockObject|MomentumCallbackInterface
     */
    private $momentumCallbackMock;

    /**
     * @var MockObject|Logger
     */
    private $loggerMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapterMock               = $this->createMock(AdapterInterface::class);
        $this->swiftMessageServiceMock   = $this->createMock(SwiftMessageServiceInterface::class);
        $this->swiftMessageValidatorMock = $this->createMock(SwiftMessageValidatorInterface::class);
        $this->momentumCallbackMock      = $this->createMock(MomentumCallbackInterface::class);
        $this->loggerMock                = $this->createMock(Logger::class);
    }

    public function testSendOk(): void
    {
        $swiftMessageMock      = $this->createMock(\Swift_Mime_SimpleMessage::class);
        $sparkPostResponseMock = $this->createMock(SparkPostResponse::class);
        $transmissionDTOMock   = $this->createMock(TransmissionDTO::class);
        $sparkPostPromiseMock  = $this->createMock(SparkPostPromise::class);
        $totalRecipients       = 0;
        $bodyResults           = [
            'results' => [
                'total_accepted_recipients' => $totalRecipients,
            ],
        ];

        $this->swiftMessageValidatorMock->expects($this->once())
            ->method('validate')
            ->with($swiftMessageMock);

        $this->swiftMessageServiceMock->expects($this->once())
            ->method('transformToTransmission')
            ->with($swiftMessageMock)
            ->willReturn($transmissionDTOMock);

        $this->adapterMock->expects($this->once())
            ->method('createTransmission')
            ->with($transmissionDTOMock)
            ->willReturn($sparkPostPromiseMock);

        $sparkPostPromiseMock->expects($this->once())
            ->method('wait')
            ->willReturn($sparkPostResponseMock);

        $sparkPostResponseMock->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn('200');

        $sparkPostResponseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($bodyResults);

        $this->momentumCallbackMock->expects($this->once())
            ->method('processImmediateFeedback')
            ->with($swiftMessageMock, $bodyResults);

        $this->assertSame($totalRecipients, $this->getMomentumFacade()->send($swiftMessageMock));
    }

    /**
     * Test for SwiftMessageValidationException exception.
     */
    public function testSendValidatorError(): void
    {
        $swiftMessageMock                    = $this->createMock(\Swift_Mime_SimpleMessage::class);
        $exceptionMessage                    = 'Example exception message';
        $swiftMessageValidationExceptionMock = new SwiftMessageValidationException($exceptionMessage);

        $this->swiftMessageValidatorMock->expects($this->once())
            ->method('validate')
            ->with($swiftMessageMock)
            ->willThrowException($swiftMessageValidationExceptionMock);

        $this->loggerMock->expects($this->once())
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
    public function testSend500FirstAttempt(): void
    {
        $swiftMessageMock         = $this->createMock(\Swift_Mime_SimpleMessage::class);
        $transmissionDTOMock      = $this->createMock(TransmissionDTO::class);
        $sparkPostPromiseMock     = $this->createMock(SparkPostPromise::class);
        $sparkPostResponseMock500 = $this->createMock(SparkPostResponse::class);
        $sparkPostResponseMock200 = $this->createMock(SparkPostResponse::class);
        $totalRecipients          = 0;
        $bodyResults              = [
            'results' => [
                'total_accepted_recipients' => $totalRecipients,
            ],
        ];

        $this->swiftMessageServiceMock->expects($this->once())
            ->method('transformToTransmission')
            ->with($swiftMessageMock)
            ->willReturn($transmissionDTOMock);

        $this->adapterMock->expects($this->exactly(2))
            ->method('createTransmission')
            ->with($transmissionDTOMock)
            ->willReturn($sparkPostPromiseMock);

        $sparkPostPromiseMock->expects($this->exactly(2))
            ->method('wait')
            ->willReturn($sparkPostResponseMock500, $sparkPostResponseMock200);

        $sparkPostResponseMock500->expects($this->once())
            ->method('getStatusCode')
            ->willReturn('500');

        $sparkPostResponseMock200->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn('200');

        $sparkPostResponseMock200->expects($this->once())
            ->method('getBody')
            ->willReturn($bodyResults);

        $this->momentumCallbackMock->expects($this->once())
            ->method('processImmediateFeedback')
            ->with($swiftMessageMock, $bodyResults);

        $this->assertSame($totalRecipients, $this->getMomentumFacade()->send($swiftMessageMock));
    }

    /**
     * Test for correct handle of repeated 500s.
     */
    public function testSend500Repeated(): void
    {
        $swiftMessageMock       = $this->createMock(\Swift_Mime_SimpleMessage::class);
        $transmissionDTOMock    = $this->createMock(TransmissionDTO::class);
        $sparkPostPromiseMock   = $this->createMock(SparkPostPromise::class);
        $sparkPostResponseMock1 = $this->createMock(SparkPostResponse::class);
        $sparkPostResponseMock2 = $this->createMock(SparkPostResponse::class);
        $sparkPostResponseMock3 = $this->createMock(SparkPostResponse::class);
        $responseBody           = 'Empty';

        $this->swiftMessageServiceMock->expects($this->once())
            ->method('transformToTransmission')
            ->with($swiftMessageMock)
            ->willReturn($transmissionDTOMock);

        $this->adapterMock->expects($this->exactly(3))
            ->method('createTransmission')
            ->with($transmissionDTOMock)
            ->willReturn($sparkPostPromiseMock);

        $sparkPostResponseMock1->expects($this->once())
            ->method('getStatusCode')
            ->willReturn('500');

        $sparkPostPromiseMock->expects($this->exactly(3))
            ->method('wait')
            ->willReturnOnConsecutiveCalls(
                $sparkPostResponseMock1,
                $sparkPostResponseMock2,
                $sparkPostResponseMock3
            );

        $sparkPostResponseMock2->expects($this->once())
            ->method('getStatusCode')
            ->willReturn('500');

        $sparkPostResponseMock3->expects($this->exactly(3))
            ->method('getStatusCode')
            ->willReturn('500');

        $sparkPostResponseMock3->expects($this->exactly(2))
            ->method('getBody')
            ->willReturn($responseBody);

        $this->loggerMock->expects($this->exactly(2))
            ->method('addError')
            ->withConsecutive(
                [
                    'Momentum send: 500', [
                        'response' => $responseBody,
                    ],
                ],
                [
                    'Momentum send exception', [
                        'message' => $responseBody,
                    ],
                ]
            );

        $facade = $this->getMomentumFacade();

        $this->expectException(MomentumSendException::class);

        $facade->send($swiftMessageMock);
    }

    private function getMomentumFacade(): MomentumFacade
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
