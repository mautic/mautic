<?php

namespace Mautic\SmsBundle\Tests\Integration\Twilio;

use Mautic\SmsBundle\Helper\ContactHelper;
use Mautic\SmsBundle\Integration\Twilio\Configuration;
use Mautic\SmsBundle\Integration\Twilio\TwilioCallback;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TwilioCallbackTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContactHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $contactHelper;

    /**
     * @var Configuration|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $configuration;

    protected function setUp(): void
    {
        $this->contactHelper = $this->createMock(ContactHelper::class);
        $this->configuration = $this->createMock(Configuration::class);
        $this->configuration->method('getAccountSid')
            ->willReturn('123');
    }

    public function testMissingFromThrowsBadRequestException(): void
    {
        $this->expectException(BadRequestHttpException::class);

        $request          = $this->createMock(Request::class);
        $inputBag         = new InputBag([
            'AccountSid' => '123',
            'From'       => '',
        ]);

        $request->request = $inputBag;

        $this->getCallback()->getMessage($request);
    }

    public function testMissingBodyThrowsBadRequestException(): void
    {
        $this->expectException(BadRequestHttpException::class);

        $request          = $this->createMock(Request::class);
        $inputBag         = new InputBag([
            'AccountSid' => '123',
            'From'       => '321',
            'Body'       => '',
        ]);

        $request->request = $inputBag;

        $this->getCallback()->getMessage($request);
    }

    public function testMismatchedAccountSidThrowsBadRequestException(): void
    {
        $this->expectException(BadRequestHttpException::class);

        $request          = $this->createMock(Request::class);
        $inputBag         = new InputBag([
            'AccountSid' => '321',
        ]);

        $request->request = $inputBag;

        $this->getCallback()->getMessage($request);
    }

    public function testMessageIsReturned(): void
    {
        $request      = $this->createMock(Request::class);
        $request->method('get')
            ->willReturn('Hello');

        $inputBag = new InputBag([
            'AccountSid' => '123',
            'From'       => '321',
            'Body'       => 'Hello',
        ]);

        $request->request = $inputBag;

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
