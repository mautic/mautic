<?php

namespace Mautic\EmailBundle\Tests\Swiftmailer\Momentum\Service;

use Mautic\EmailBundle\Swiftmailer\Message\MauticMessage;
use Mautic\EmailBundle\Swiftmailer\Momentum\Service\SwiftMessageService;
use Symfony\Component\Translation\TranslatorInterface;

class SwiftMessageServiceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $translatorInterfaceMock;

    protected function setUp(): void
    {
        $this->translatorInterfaceMock = $this->createMock(TranslatorInterface::class);
    }

    /**
     * @param string $expectedTransmissionJson
     *
     * @dataProvider dataTransformToTransmission
     */
    public function testTransformToTransmission(MauticMessage $mauticMessage, $expectedTransmissionJson)
    {
        $service         = new SwiftMessageService();
        $transmissionDTO = $service->transformToTransmission($mauticMessage);

        $this->assertJsonStringEqualsJsonString($expectedTransmissionJson, json_encode($transmissionDTO));
    }

    public function dataTransformToTransmission()
    {
        return [
            $this->geTransformToTransmissionComplexData(),
            $this->geTransformToTransmissionComplexDataWithEmailName(),
            $this->geTransformToTransmissionComplexDataWithUtmTag(),
        ];
    }

    /**
     * @return array
     */
    private function geTransformToTransmissionComplexData()
    {
        $mauticMessage = new MauticMessage();

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
               "recipients":[
                  {
                     "address":{
                        "email":"to1@test.local",
                        "name":"To1 test",
                        "header_to":"to1@test.local"
                     },
                     "substitution_data": {}
                  },
                  {
                     "address":{
                        "email":"to2@test.local",
                        "name":"To2 test",
                        "header_to":"to2@test.local"
                     },
                     "substitution_data": {}
                  },
                  {
                     "address":{
                        "email":"cc1@test.local",
                        "name":"CC1 test",
                        "header_to":"cc1@test.local"
                     },
                     "substitution_data": {}
                  },
                  {
                     "address":{
                        "email":"cc2@test.local",
                        "name":"CC2 test",
                        "header_to":"cc2@test.local"
                     },
                     "substitution_data": {}
                  },
                  {
                     "address":{
                        "email":"bcc1@test.local",
                        "name":"BCC1 test"
                     },
                     "substitution_data": {}
                  },
                  {
                     "address":{
                        "email":"bcc2@test.local",
                        "name":"BCC2 test"
                     },
                     "substitution_data": {}
                  }
               ],
               "content":{
                  "subject":"Test subject",
                  "from":{
                     "email":"from@test.local",
                     "name":"From test"
                  },
                  "html":"<html><\/html>",
                  "headers":{
                     "CC":"cc1@test.local,cc2@test.local"
                  },
                  "attachments":[
                     {
                        "type":"text\/plain",
                        "name":"sample.txt",
                        "data":"VGhpcyBpcyBzYW1wbGUgYXR0YWNobWVudAo="
                     }
                  ]
               }
            }
        ';

        return [$mauticMessage, $json];
    }

    /**
     * @return array
     */
    private function geTransformToTransmissionComplexDataWithEmailName()
    {
        $mauticMessage = new MauticMessage();
        $mauticMessage->addMetadata(
            'to1@test.local',
            [
                'emailId'   => 1,
                'emailName' => 'Email Name',
                'tokens'    => [
                    '{hashId}' => '1234',
                ],
            ]
        );
        $mauticMessage->addMetadata(
            'to2@test.local',
            [
                'emailId'   => 1,
                'emailName' => 'Email Name',
                'tokens'    => [
                    '{hashId}' => '4321',
                ],
            ]
        );

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
               "recipients":[
                  {
                     "address":{
                        "email":"to1@test.local",
                        "name":"To1 test",
                        "header_to":"to1@test.local"
                     },
                     "metadata":{
                        "emailId":1,
                        "emailName":"Email Name"
                     },
                     "substitution_data":{
                        "HASHID":"1234"
                     }
                  },
                  {
                     "address":{
                        "email":"to2@test.local",
                        "name":"To2 test",
                        "header_to":"to2@test.local"
                     },
                     "metadata":{
                        "emailId":1,
                        "emailName":"Email Name"
                     },
                     "substitution_data":{
                        "HASHID":"4321"
                     }
                  },
                  {
                     "address":{
                        "email":"cc1@test.local",
                        "name":"CC1 test",
                        "header_to":"cc1@test.local"
                     },
                     "substitution_data": {}
                  },
                  {
                     "address":{
                        "email":"cc2@test.local",
                        "name":"CC2 test",
                        "header_to":"cc2@test.local"
                     },
                     "substitution_data": {}
                  },
                  {
                     "address":{
                        "email":"bcc1@test.local",
                        "name":"BCC1 test"
                     },
                     "substitution_data": {}
                  },
                  {
                     "address":{
                        "email":"bcc2@test.local",
                        "name":"BCC2 test"
                     },
                     "substitution_data": {}
                  }
               ],
               "content":{
                  "subject":"Test subject",
                  "from":{
                     "email":"from@test.local",
                     "name":"From test"
                  },
                  "html":"<html><\/html>",
                  "headers":{
                     "CC":"cc1@test.local,cc2@test.local"
                  },
                  "attachments":[
                     {
                        "type":"text\/plain",
                        "name":"sample.txt",
                        "data":"VGhpcyBpcyBzYW1wbGUgYXR0YWNobWVudAo="
                     }
                  ]
               },
               "campaign_id":"1:Email Name"
            }
        ';

        return [$mauticMessage, $json];
    }

    /**
     * @return array
     */
    private function geTransformToTransmissionComplexDataWithUtmTag()
    {
        $metadata = [
            'emailName' => 'Email Name',
            'utmTags'   => [
                'utmCampaign' => 'Custom Name',
            ],
        ];

        $mauticMessage = new MauticMessage();
        $mauticMessage->addMetadata(
            'to1@test.local',
            array_merge(
                $metadata,
                [
                    'tokens' => [
                        '{hashId}' => '1234',
                    ],
                ]
            )
        );
        $mauticMessage->addMetadata(
            'to2@test.local',
            array_merge(
                $metadata,
                [
                    'tokens' => [
                        '{hashId}' => '4321',
                    ],
                ]
            )
        );

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
               "recipients":[
                  {
                     "address":{
                        "email":"to1@test.local",
                        "name":"To1 test",
                        "header_to":"to1@test.local"
                     },
                     "metadata":{
                        "emailName":"Email Name",
                        "utmTags":{
                            "utmCampaign": "Custom Name"
                        }
                     },
                     "substitution_data":{
                        "HASHID":"1234"
                     }
                  },
                  {
                     "address":{
                        "email":"to2@test.local",
                        "name":"To2 test",
                        "header_to":"to2@test.local"
                     },
                     "metadata":{
                        "emailName":"Email Name",
                         "utmTags":{
                            "utmCampaign": "Custom Name"
                        }
                     },
                     "substitution_data":{
                        "HASHID":"4321"
                     }
                  },
                  {
                     "address":{
                        "email":"cc1@test.local",
                        "name":"CC1 test",
                        "header_to":"cc1@test.local"
                     },
                     "substitution_data": {}
                  },
                  {
                     "address":{
                        "email":"cc2@test.local",
                        "name":"CC2 test",
                        "header_to":"cc2@test.local"
                     },
                     "substitution_data": {}
                  },
                  {
                     "address":{
                        "email":"bcc1@test.local",
                        "name":"BCC1 test"
                     },
                     "substitution_data": {}
                  },
                  {
                     "address":{
                        "email":"bcc2@test.local",
                        "name":"BCC2 test"
                     },
                     "substitution_data": {}
                  }
               ],
               "content":{
                  "subject":"Test subject",
                  "from":{
                     "email":"from@test.local",
                     "name":"From test"
                  },
                  "html":"<html><\/html>",
                  "headers":{
                     "CC":"cc1@test.local,cc2@test.local"
                  },
                  "attachments":[
                     {
                        "type":"text\/plain",
                        "name":"sample.txt",
                        "data":"VGhpcyBpcyBzYW1wbGUgYXR0YWNobWVudAo="
                     }
                  ]
               },
               "campaign_id":"Custom Name"
            }
        ';

        return [$mauticMessage, $json];
    }
}
