<?php

namespace Mautic\EmailBundle\Tests\Transport;

use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Model\TransportCallback;
use Mautic\EmailBundle\Swiftmailer\Message\MauticMessage;
use Mautic\EmailBundle\Swiftmailer\Sparkpost\SparkpostFactoryInterface;
use Mautic\EmailBundle\Swiftmailer\Transport\SparkpostTransport;
use Psr\Log\LoggerInterface;

class SparkpostTransportMessageTest extends \PHPUnit\Framework\TestCase
{
    public function testCcAndBccFields()
    {
        $translator        = $this->createMock(Translator::class);
        $transportCallback = $this->createMock(TransportCallback::class);
        $sparkpostFactory  = $this->createMock(SparkpostFactoryInterface::class);
        $logger            = $this->createMock(LoggerInterface::class);

        $message = new MauticMessage('Test subject', 'First Name: {formfield=first_name}');
        $message->addFrom('from@xx.xx');

        $message->addTo('to1@xx.xx');
        $message->addTo('to2@xx.xx');

        $message->addCc('cc1@xx.xx');
        $message->addCc('cc2@xx.xx');

        $message->addBcc('bcc1@xx.xx');
        $message->addBcc('bcc2@xx.xx');

        $message->addMetadata(
            'to1@xx.xx',
            [
                'tokens' => [
                    '{formfield=first_name}' => '1',
                ],
            ]
        );

        $message->addMetadata(
            'to2@xx.xx',
            [
                'tokens' => [
                    '{formfield=first_name}' => '2',
                ],
            ]
        );

        $sparkpost = new SparkpostTransport('1234', $translator, $transportCallback, $sparkpostFactory, $logger);

        $sparkpostMessage = $sparkpost->getSparkPostMessage($message);

        $this->assertEquals('from@xx.xx', $sparkpostMessage['content']['from']);
        $this->assertEquals('Test subject', $sparkpostMessage['content']['subject']);
        $this->assertEquals('First Name: {{{ FORMFIELDFIRSTNAME }}}', $sparkpostMessage['content']['html']);

        $this->assertCount(10, $sparkpostMessage['recipients']);

        //CC and BCC fields has to be included as normal recipient with same data as TO fields has
        $recipients = [
            [
                'address' => [
                    'email' => 'to1@xx.xx',
                    'name'  => null,
                ],
                'substitution_data' => [
                    'FORMFIELDFIRSTNAME' => '1',
                ],
            ],
            [
                'address' => [
                    'email' => 'cc1@xx.xx',
                ],
                'header_to'         => 'to1@xx.xx',
                'substitution_data' => [
                    'FORMFIELDFIRSTNAME' => '1',
                ],
            ],
            [
                'address' => [
                    'email' => 'cc2@xx.xx',
                ],
                'header_to'         => 'to1@xx.xx',
                'substitution_data' => [
                    'FORMFIELDFIRSTNAME' => '1',
                ],
            ],
            [
                'address' => [
                    'email' => 'bcc1@xx.xx',
                ],
                'header_to'         => 'to1@xx.xx',
                'substitution_data' => [
                    'FORMFIELDFIRSTNAME' => '1',
                ],
            ],
            [
                'address' => [
                    'email' => 'bcc2@xx.xx',
                ],
                'header_to'         => 'to1@xx.xx',
                'substitution_data' => [
                    'FORMFIELDFIRSTNAME' => '1',
                ],
            ],
            [
                'address' => [
                    'email' => 'to2@xx.xx',
                    'name'  => null,
                ],
                'substitution_data' => [
                    'FORMFIELDFIRSTNAME' => '2',
                ],
            ],
            [
                'address' => [
                    'email' => 'cc1@xx.xx',
                ],
                'header_to'         => 'to2@xx.xx',
                'substitution_data' => [
                    'FORMFIELDFIRSTNAME' => '2',
                ],
            ],
            [
                'address' => [
                    'email' => 'cc2@xx.xx',
                ],
                'header_to'         => 'to2@xx.xx',
                'substitution_data' => [
                    'FORMFIELDFIRSTNAME' => '2',
                ],
            ],
            [
                'address' => [
                    'email' => 'bcc1@xx.xx',
                ],
                'header_to'         => 'to2@xx.xx',
                'substitution_data' => [
                    'FORMFIELDFIRSTNAME' => '2',
                ],
            ],
            [
                'address' => [
                    'email' => 'bcc2@xx.xx',
                ],
                'header_to'         => 'to2@xx.xx',
                'substitution_data' => [
                    'FORMFIELDFIRSTNAME' => '2',
                ],
            ],
        ];

        $this->assertEquals($recipients, $sparkpostMessage['recipients']);
    }
}
