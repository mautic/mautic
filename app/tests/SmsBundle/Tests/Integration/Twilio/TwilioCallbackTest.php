<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Integration\Twilio;

use Mautic\SmsBundle\Helper\ContactHelper;
use Mautic\SmsBundle\Integration\Twilio\Configuration;
use Mautic\SmsBundle\Integration\Twilio\TwilioCallback;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AllowMockObjectsWithoutExpectations]
final class TwilioCallbackTest extends TestCase
{
    /**
     * @var MockObject&Configuration
     */
    private MockObject $configuration;

    protected function setUp(): void
    {
        $this->configuration = $this->createMock(Configuration::class);
        $this->configuration->method('getAccountSid')
            ->willReturn('123');
    }

    public function testMissingFromThrowsBadRequestException(): void
    {
        $this->expectException(BadRequestHttpException::class);

        $request = new Request([], [
            'AccountSid' => '123',
            'From'       => '',
        ]);

        $this->getCallback()->getMessage($request);
    }

    public function testMissingBodyThrowsBadRequestException(): void
    {
        $this->expectException(BadRequestHttpException::class);

        $request = new Request([], [
            'AccountSid' => '123',
            'From'       => '321',
            'Body'       => '',
        ]);

        $this->getCallback()->getMessage($request);
    }

    public function testMismatchedAccountSidThrowsBadRequestException(): void
    {
        $this->expectException(BadRequestHttpException::class);

        $request = new Request([], [
            'AccountSid' => '321',
        ]);

        $this->getCallback()->getMessage($request);
    }

    public function testMessageIsReturned(): void
    {
        $request = new Request(request: [
            'AccountSid' => '123',
            'From'       => '321',
            'Body'       => 'Hello',
        ]);

        $this->assertSame('Hello', $this->getCallback()->getMessage($request));
    }

    private function getCallback(): TwilioCallback
    {
        return new TwilioCallback($this->createStub(ContactHelper::class), $this->configuration);
    }
}
