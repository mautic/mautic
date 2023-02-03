<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Swiftmailer\Amazon;

use GuzzleHttp\Client;
use Mautic\EmailBundle\Model\TransportCallback;
use Mautic\EmailBundle\Swiftmailer\Amazon\AmazonCallback;
use Mautic\LeadBundle\Entity\DoNotContact;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AmazonCallbackTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|TranslatorInterface
     */
    private $translator;

    /**
     * @var MockObject|LoggerInterface
     */
    private $logger;

    /**
     * @var MockObject|Client
     */
    private $httpClient;

    /**
     * @var MockObject|TransportCallback
     */
    private $transportCallback;

    private AmazonCallback $amazonCallback;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator        = $this->createMock(TranslatorInterface::class);
        $this->logger            = $this->createMock(LoggerInterface::class);
        $this->httpClient        = $this->createMock(Client::class);
        $this->transportCallback = $this->createMock(TransportCallback::class);
        $this->amazonCallback    = new AmazonCallback(
            $this->translator,
            $this->logger,
            $this->httpClient,
            $this->transportCallback
        );
    }

    public function testProcessJsonPayloadWithBounce(): void
    {
        $payload = [
            'notificationType' => 'Bounce',
            'bounce'           => [
                'feedbackId'        => '010201961265bd3d-31708607-e7c7-4722-9957-bd75c36254d7-000000',
                'bounceType'        => 'Permanent',
                'bounceSubType'     => 'General',
                'bouncedRecipients' => [
                    [
                        'emailAddress'   => 'recipient@example.com',
                        'action'         => 'failed',
                        'status'         => '5.0.0',
                        'diagnosticCode' => 'smtp; 550 Message was not accepted',
                    ],
                ],
                'timestamp'    => '2023-02-02T21:37:43.000Z',
                'reportingMTA' => 'dns; example.i',
            ],
            'mail' => [
                'timestamp'        => '2023-02-02T09:40:21.755Z',
                'source'           => '"Example" <sender@example.com>',
                'sourceArn'        => 'arn:aws:ses:eu-west-1:6666666666666:identity/sender@example.com',
                'sourceIp'         => '127.0.0.1',
                'callerIdentity'   => 'ses-api-mautic-prod',
                'sendingAccountId' => '756458876448',
                'messageId'        => '01020186117f9e7b-1c367b9f-15c9-45f1-b8a0-87e3fbad01e1-000000',
                'destination'      => [
                    0 => 'recipient@example.com',
                ],
                'headersTruncated' => false,
                'headers'          => [
                    [
                        'name'  => 'Return-Path',
                        'value' => '<no-reply@example.com>',
                    ],
                    [
                        'name'  => 'Message-ID',
                        'value' => '<bcf9238756545345567768311710d0c3@swift.generated>',
                    ],
                    [
                        'name'  => 'Date',
                        'value' => 'Thu, 02 Feb 2023 10:40:21 +0100',
                    ],
                    [
                        'name'  => 'Subject',
                        'value' => 'Be Part of the Business 4.0 Revolution',
                    ],
                    [
                        'name'  => 'From',
                        'value' => 'Example <sender@example.com>',
                    ],
                    [
                        'name'  => 'Reply-To',
                        'value' => 'sender@example.com',
                    ],
                    [
                        'name'  => 'To',
                        'value' => 'recipient@example.com',
                    ],
                    [
                        'name'  => 'X-EMAIL-ID',
                        'value' => '7',
                    ],
                ],
                'commonHeaders' => [
                    'returnPath' => 'no-reply@example.com',
                    'from'       => ['Example <sender@example.com>'],
                    'replyTo'    => ['sender@example.com'],
                    'date'       => 'Thu, 02 Feb 2023 10:40:21 +0100',
                    'to'         => ['recipient@example.com'],
                    'messageId'  => '<bcf92370afb2048f6a5ea3311710d0c3@swift.generated>',
                    'subject'    => 'Be Part of the Business 4.0 Revolution',
                ],
            ],
        ];

        $this->transportCallback->expects($this->once())
            ->method('addFailureByAddress')
            ->with('recipient@example.com', 'smtp; 550 Message was not accepted', DoNotContact::BOUNCED, '7');

        $this->amazonCallback->processJsonPayload($payload, 'Bounce');
    }

    public function testProcessJsonPayloadWithComplaint(): void
    {
        $payload = [
            'notificationType' => 'Complaint',
            'complaint'        => [
                'feedbackId'           => '010201961265bd3d-31708607-e7c7-4722-9957-bd75c36254d7-000000',
                'complaintSubType'     => null,
                'complainedRecipients' => [
                    [
                        'emailAddress' => 'recipient@example.com',
                    ],
                ],
                'timestamp'   => '2023-02-02T21:37:43.000Z',
                'arrivalDate' => '2023-02-02T21:37:43.586Z',
            ],
            'mail' => [
                'timestamp'        => '2023-02-02T09:40:21.755Z',
                'source'           => '"Example" <sender@example.com>',
                'sourceArn'        => 'arn:aws:ses:eu-west-1:6666666666666:identity/sender@example.com',
                'sourceIp'         => '127.0.0.1',
                'callerIdentity'   => 'ses-api-mautic-prod',
                'sendingAccountId' => '756458876448',
                'messageId'        => '01020186117f9e7b-1c367b9f-15c9-45f1-b8a0-87e3fbad01e1-000000',
                'destination'      => [
                    0 => 'recipient@example.com',
                ],
                'headersTruncated' => false,
                'headers'          => [
                    [
                        'name'  => 'Return-Path',
                        'value' => '<no-reply@example.com>',
                    ],
                    [
                        'name'  => 'Message-ID',
                        'value' => '<bcf9238756545345567768311710d0c3@swift.generated>',
                    ],
                    [
                        'name'  => 'Date',
                        'value' => 'Thu, 02 Feb 2023 10:40:21 +0100',
                    ],
                    [
                        'name'  => 'Subject',
                        'value' => 'Be Part of the Business 4.0 Revolution',
                    ],
                    [
                        'name'  => 'From',
                        'value' => 'Example <sender@example.com>',
                    ],
                    [
                        'name'  => 'Reply-To',
                        'value' => 'sender@example.com',
                    ],
                    [
                        'name'  => 'To',
                        'value' => 'recipient@example.com',
                    ],
                    [
                        'name'  => 'X-EMAIL-ID',
                        'value' => '7',
                    ],
                ],
                'commonHeaders' => [
                    'returnPath' => 'no-reply@example.com',
                    'from'       => ['Example <sender@example.com>'],
                    'replyTo'    => ['sender@example.com'],
                    'date'       => 'Thu, 02 Feb 2023 10:40:21 +0100',
                    'to'         => ['recipient@example.com'],
                    'messageId'  => '<bcf92370afb2048f6a5ea3311710d0c3@swift.generated>',
                    'subject'    => 'Be Part of the Business 4.0 Revolution',
                ],
            ],
        ];

        $this->translator->expects($this->any())
            ->method('trans')
            ->with('mautic.email.complaint.reason.unknown')
            ->willReturn('unknown');

        $this->transportCallback->expects($this->once())
            ->method('addFailureByAddress')
            ->with('recipient@example.com', 'unknown', DoNotContact::UNSUBSCRIBED, '7');

        $this->amazonCallback->processJsonPayload($payload, 'Complaint');
    }
}
