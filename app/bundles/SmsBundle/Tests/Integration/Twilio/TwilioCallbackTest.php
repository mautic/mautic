<?php

namespace Mautic\SmsBundle\Tests\Integration\Twilio;

use Mautic\SmsBundle\Helper\ContactHelper;
use Mautic\SmsBundle\Integration\Twilio\Configuration;
use Mautic\SmsBundle\Integration\Twilio\TwilioCallback;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TwilioCallbackTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContactHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactHelper;

    /**
     * @var Configuration|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configuration;

    protected function setUp(): void
    {
        $this->contactHelper = $this->createMock(ContactHelper::class);
        $this->configuration = $this->createMock(Configuration::class);
        $this->configuration->method('getAccountSid')
            ->willReturn('123');
    }

    public function testMissingFromThrowsBadRequestException()
    {
        $this->expectException(BadRequestHttpException::class);

        $parameterBag     = $this->createMock(ParameterBag::class);
        $request          = $this->createMock(Request::class);
        $request->request = $parameterBag;

        $parameterBag->method('get')
            ->withConsecutive(['AccountSid'], ['From'])
            ->willReturn('123', '');

        $this->getCallback()->getMessage($request);
    }

    public function testMissingBodyThrowsBadRequestException()
    {
        $this->expectException(BadRequestHttpException::class);

        $parameterBag     = $this->createMock(ParameterBag::class);
        $request          = $this->createMock(Request::class);
        $request->request = $parameterBag;

        $parameterBag->method('get')
            ->withConsecutive(['AccountSid'], ['From'], ['Body'])
            ->willReturn('123', '321', '');

        $this->getCallback()->getMessage($request);
    }

    public function testMismatchedAccountSidThrowsBadRequestException()
    {
        $this->expectException(BadRequestHttpException::class);

        $parameterBag     = $this->createMock(ParameterBag::class);
        $request          = $this->createMock(Request::class);
        $request->request = $parameterBag;

        $parameterBag->method('get')
            ->withConsecutive(['AccountSid'])
            ->willReturn('321');

        $this->getCallback()->getMessage($request);
    }

    public function testMessageIsReturned()
    {
        $parameterBag = $this->createMock(ParameterBag::class);
        $request      = $this->createMock(Request::class);
        $request->method('get')
            ->willReturn('Hello');
        $request->request = $parameterBag;

        $parameterBag->method('get')
            ->withConsecutive(['AccountSid'], ['From'], ['Body'])
            ->willReturn('123', '321', 'Hello');

        $this->assertEquals('Hello', $this->getCallback()->getMessage($request));
    }

    /**
     * @return TwilioCallback
     */
    private function getCallback()
    {
        return new TwilioCallback($this->contactHelper, $this->configuration);
    }
}
