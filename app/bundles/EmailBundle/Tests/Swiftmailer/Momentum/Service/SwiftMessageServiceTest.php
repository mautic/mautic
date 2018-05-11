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

    /**
     * @param MauticMessage $mauticMessage
     * @param string        $expectedTransmissionJson
     *
     * @dataProvider dataTransformToTransmission
     */
    public function testTransformToTransmission(MauticMessage $mauticMessage, $expectedTransmissionJson)
    {
        $service         = $this->getSwiftMessageService();
        $transmissionDTO = $service->transformToTransmission($mauticMessage);

        $this->assertJsonStringEqualsJsonString($expectedTransmissionJson, json_encode($transmissionDTO));
    }

    public function dataTransformToTransmission()
    {
        return [
            $this->geTransformToTransmissionComplexData(),
        ];
    }

    /**
     * @return array
     */
    private function geTransformToTransmissionComplexData()
    {
        $mauticMessage   = new MauticMessage();
        $mauticMessage->setSubject('Test subject')
            ->setReturnPath('return-path@test.local')
            ->setSender('sender@test.local', 'Sender test')
            ->setFrom('from@test.local', 'From test')
            ->setBody('<html></html>')
            ->addTo('to1@test.local', 'To1 test')
            ->addTo('to2@test.local', 'To2 test')
            ->addCc('cc1@test.local', 'CC1 test')
            ->addCc('cc2@test.local', 'CC2 test')
            ->addBcc('bcc1@test.local', 'BCC1 test')
            ->addBcc('bcc2@test.local', 'BCC2 test')
            ->addAttachment(__DIR__.'/data/attachments/sample.txt');
        $json = '
            {
                "return_path":"return-path@test.local",
                "recipients": [
                    {
                        "address": {
                            "email": "to1@test.local",
                            "name": "To1 test",
                            "header_to": "to1@test.local"
                        }
                    },
                    {
                        "address": {
                            "email": "to2@test.local",
                            "name": "To2 test",
                            "header_to": "to2@test.local"
                        }
                    },
                    {
                        "address": {
                            "email": "cc1@test.local",
                            "name": "CC1 test",
                            "header_to": "cc1@test.local"
                        }
                    },
                    {
                        "address": {
                            "email": "cc2@test.local",
                            "name": "CC2 test",
                            "header_to": "cc2@test.local"
                        }
                    },
                    {
                        "address": {
                            "email": "bcc1@test.local",
                            "name": "BCC1 test"
                        }
                    },
                    {
                        "address": {
                            "email": "bcc2@test.local",
                            "name": "BCC2 test"
                        }
                    }
                ],
                "content": {
                    "subject": "Test subject",
                    "from": {
                        "email": "from@test.local",
                        "name": "From test"
                    },
                    "html": "<html><\/html>",
                    "attachments": [
                        {
                            "type": "text\/plain",
                            "name": "sample.txt",
                            "content": "VGhpcyBpcyBzYW1wbGUgYXR0YWNobWVudAo="
                        }
                    ]
                }
            }
        ';

        return [$mauticMessage, $json];
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
