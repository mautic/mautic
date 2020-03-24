<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Tests\Integration\Twilio;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Helper\ContactHelper;
use Mautic\SmsBundle\Integration\Twilio\Configuration;
use Mautic\SmsBundle\Integration\Twilio\TwilioCallback;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TwilioCallbackTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContactHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contactHelper;

    /**
     * @var Configuration|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configuration;

    protected function setUp()
    {
        $this->contactHelper = $this->createMock(ContactHelper::class);
        $this->configuration = $this->createMock(Configuration::class);
        $this->configuration->method('getAccountSid')
            ->willReturn('123');
        $this->contactHelper->method('findContactsByNumber')
            ->willReturn([new Lead()]);
    }

    public function testMissingFromThrowsBadRequestException()
    {
        $this->expectException(BadRequestHttpException::class);

        $parameterBag     = $this->createMock(ParameterBag::class);
        $request          = $this->createMock(Request::class);

        $parameterBag->method('get')
            ->withConsecutive(['AccountSid'], ['From'], ['Body'])
            ->willReturn('123', '321', '');
        $request->request = $parameterBag;
        $this->getCallback()->getCallbackEvent($request);
    }

    public function testMissingBodyThrowsBadRequestException()
    {
        $this->expectException(BadRequestHttpException::class);

        $parameterBag     = $this->createMock(ParameterBag::class);
        $request          = $this->createMock(Request::class);

        $parameterBag->method('get')
            ->withConsecutive(['AccountSid'], ['From'], ['Body'])
            ->willReturn('123', '321', '');
        $request->request = $parameterBag;
        $this->getCallback()->getCallbackEvent($request);
    }

    public function testMismatchedAccountSidThrowsBadRequestException()
    {
        $this->expectException(BadRequestHttpException::class);

        $parameterBag     = $this->createMock(ParameterBag::class);
        $request          = $this->createMock(Request::class);

        $parameterBag->method('get')
            ->withConsecutive(['AccountSid'], ['From'], ['Body'])
            ->willReturn('123', '321', '');

        $request->request = $parameterBag;
        $this->getCallback()->getCallbackEvent($request);
    }

    public function testMessageIsReturned()
    {
        $this->expectException(BadRequestHttpException::class);

        $parameterBag = $this->createMock(ParameterBag::class);
        $request      = $this->createMock(Request::class);
        $parameterBag->method('get')
            ->withConsecutive(['AccountSid'], ['From'], ['Body'])
            ->willReturn('123', '321', 'Hello');
        $request->request = $parameterBag;
        $this->assertEquals('Hello', $this->getCallback()->getCallbackEvent($request)->getMessage());
    }

    /**
     * @return TwilioCallback
     */
    private function getCallback()
    {
        return new TwilioCallback($this->contactHelper, $this->configuration);
    }
}
