<?php

namespace Mautic\EmailBundle\Tests\Swiftmailer\Momentum\Service;

use Mautic\EmailBundle\Swiftmailer\Message\MauticMessage;
use Mautic\EmailBundle\Swiftmailer\Momentum\Service\SwiftMessageService;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class SwiftMessageServiceTest.
 */
class SwiftMessageServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $translatorInterfaceMock;

    protected function setUp()
    {
        $this->translatorInterfaceMock = $this->createMock(TranslatorInterface::class);
    }

    public function testTransformToTransmission()
    {
        $mauticMessage   = new MauticMessage();
        $service         = $this->getSwiftMessageService();
        $transmissionDTO = $service->transformToTransmission($mauticMessage);
    }

    /**
     * @return SwiftMessageService
     */
    private function getSwiftMessageService()
    {
        return new SwiftMessageService(
            $this->translatorInterfaceMock
        );
    }
}
