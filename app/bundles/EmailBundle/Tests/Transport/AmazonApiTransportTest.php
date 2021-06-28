<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 */

namespace Mautic\EmailBundle\Tests\Transport;

use Aws\CommandInterface;
use Aws\Credentials\Credentials;
use Aws\Exception\AwsException;
use Aws\MockHandler;
use Aws\Result;
use Aws\SesV2\SesV2Client;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mautic\EmailBundle\Model\TransportCallback;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\BouncedEmail;
use Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription\UnsubscribedEmail;
use Mautic\EmailBundle\Swiftmailer\Amazon\AmazonCallback;
use Mautic\EmailBundle\Swiftmailer\Message\MauticMessage;
use Mautic\EmailBundle\Swiftmailer\Transport\AmazonApiTransport;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Translation\TranslatorInterface;

class AmazonApiTransportTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|TranslatorInterface
     */
    private $translator;

    /**
     * @var MockObject|AmazonCallback
     */
    private $amazonCallback;
    private $amazonMock;
    private $amazonTransport;
    private $logger;
    private $headers;
    private $request;

    /**
     * @var MockObject|Client
     */
    private $mockHttp;
    /**
     * @var MockObject|TransportCallback
     */
    private $transportCallback;

    /**
     * @var MockObject|MauticMessage
     */
    private $message;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator         = $this->createMock(TranslatorInterface::class);
        $this->amazonCallback     = $this->createMock(AmazonCallback::class);
        $this->logger             = $this->createMock(LoggerInterface::class);
        $this->message            = $this->createMock(MauticMessage::class);
        $this->headers            = $this->createMock(\Swift_Mime_SimpleHeaderSet::class);
        $this->request            =  $this->createMock(Request::class);

        // Mock http connector
        $this->mockHttp          = $this->createMock(Client::class);
        $this->transportCallback = $this->createMock(TransportCallback::class);
        $this->amazonMock        = new MockHandler();

        $this->amazonTransport   = new AmazonApiTransport(
            $this->translator,
            $this->amazonCallback,
            $this->logger
        );

        $this->amazonTransport->setRegion('us-east-1', '');
        $this->amazonTransport->setUsername('username');
        $this->amazonTransport->setPassword('password');
        $this->amazonTransport->setHandler($this->amazonMock);
        $this->amazonTransport->setDebug(true);

        $this->translator->method('trans')
            ->willReturnCallback(function ($key) {
                return $key;
            });

        $this->message->method('getChildren')->willReturn([]);
        $this->message->method('getMetadata')->willReturn([
            'success227@simulator.amazonses.com' => [
                'name'        => 'firstname227 lastname227',
                'leadId'      => 228,
                'emailId'     => 2,
                'emailName'   => 'simple',
                'hashId'      => '5f86a61cc8084320276637',
                'hashIdState' => true,
                'source'      => [
                    'campaign.event',
                        23,
                ],
                'tokens' => [
                    '{dynamiccontent="Dynamic Content 1"}' => 'Default Dynamic Content',
                    '{unsubscribe_text}'                   => '<a href="https://mautic3.ddev.site/email/unsubscribe/5f86a61cc8084320276637">Unsubscribe</a> to no longer receive emails from us.',
                    '{unsubscribe_url}'                    => 'https://mautic3.ddev.site/email/unsubscribe/5f86a61cc8084320276637',
                    '{webview_text}'                       => '<a href="https://mautic3.ddev.site/email/view/5f86a61cc8084320276637">Having trouble reading this email? Click here.</a>',
                    '{webview_url}'                        => 'https://mautic3.ddev.site/email/view/5f86a61cc8084320276637',
                    '{signature}'                          => 'Best regards, Mohammad Musa',
                    '{subject}'                            => 'simple message',
                    '{contactfield=firstname}'             => 'firstname227',
                    '{contactfield=lastname}'              => 'lastname227',
                    '{ownerfield=email}'                   => '',
                    '{ownerfield=firstname}'               => '',
                    '{ownerfield=lastname}'                => '',
                    '{ownerfield=position}'                => '',
                    '{ownerfield=signature}'               => '',
                    '{tracking_pixel}'                     => 'https://mautic3.ddev.site/email/5f86a61cc8084320276637.gif',
                ],
                'utmTags' => [
                    'utmSource'   => 'c_source',
                    'utmMedium'   => 'c_medium',
                    'utmCampaign' => 'c_name',
                    'utmContent'  => 'c_content',
                ],
            ],
        ]);
        $this->message->method('getSubject')->willReturn('Top secret');
        $this->message->method('getFrom')->willReturn(['john@doe.email' => 'John']);
        $this->message->method('getTo')->willReturn(['jane@doe.email' => 'Jane']);
        $this->message->method('getCc')->willReturn(['cc@doe.email' => 'Jane']);
        $this->message->method('getBcc')->willReturn(['bcc@doe.email' => 'Jane']);
        $this->message->method('getHeaders')->willReturn($this->headers);
        $this->headers->method('getAll')->willReturn([]);
        $this->message->method('getBody')->willReturn('Test Body');
        $this->message->method('getAttachments')->willReturn([]);
        $this->message->method('toString')->willReturn('test');
        $this->amazonMock->append(new Result([
          'SendQuota' => [
            'Max24HourSend'  => 1000,
            'MaxSendRate'    => 160,
            'SentLast24Hours'=> 0,
          ],
          'SendingEnabled' => true,
        ]));

        $this->amazonMock->append(new Result([
          'MessageId' => 'abcd12',
        ]));
    }

    public function testAmazonStart()
    {
        $this->assertNull($this->amazonTransport->start());
    }

    public function testAmazonSend()
    {
        $this->amazonTransport->start();
        $sent = $this->amazonTransport->send($this->message);
        $this->assertEquals(1, $sent);
    }

    public function testprocessInvalidJsonRequest()
    {
        $payload = <<< 'PAYLOAD'
{
    "Type": "Invalid
}
PAYLOAD;

        $amazonCallback = new AmazonCallback($this->translator, $this->logger, $this->mockHttp, $this->transportCallback);

        $request = $this->getMockBuilder(Request::class)
        ->disableOriginalConstructor()
        ->getMock();

        $request->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue($payload));

        $this->expectException(HttpException::class);

        $amazonCallback->processCallbackRequest($request);
    }

    public function testprocessValidJsonWithoutTypeRequest()
    {
        $payload = <<< 'PAYLOAD'
{
    "Content": "Not Type"
}
PAYLOAD;

        $amazonCallback = new AmazonCallback($this->translator, $this->logger, $this->mockHttp, $this->transportCallback);

        $request = $this->getMockBuilder(Request::class)
        ->disableOriginalConstructor()
        ->getMock();

        $request->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue($payload));

        $this->expectException(HttpException::class);

        $amazonCallback->processCallbackRequest($request);
    }

    public function testprocessSubscriptionConfirmationRequest()
    {
        $payload = <<< 'PAYLOAD'
{
    "Type" : "SubscriptionConfirmation",
    "MessageId" : "a3466e9f-872a-4438-9cf8-91d282af0f53",
    "Token" : "2336412f37fb687f5d51e6e241d44a2cbcd89f3e7ec51a160fe3cbfc82bc5853b2b75443b051bbeb52c98da19f609e9de0da18c341fe56a51b34f95203cb9bbab9fda0ba97eb5c43b3102911d6a68e05b8023efa4daeb8e217fd1c7325237d53f8e4e95fd3b0217dd13485a8f61f39478a21d55ec0a96ec0f163167053d86c76",
    "TopicArn" : "arn:aws:sns:eu-west-1:918057160339:55hubs-mautic-test",
    "Message" : "You have chosen to subscribe to the topic arn:aws:sns:eu-west-1:918057160339:55hubs-mautic-test. To confirm the subscription, visit the SubscribeURL included in this message.",
    "SubscribeURL" : "https://sns.eu-west-1.amazonaws.com/?Action=ConfirmSubscription&TopicArn=arn:aws:sns:eu-west-1:918057160339:55hubs-mautic-test&Token=2336412f37fb687f5d51e6e241d44a2cbcd89f3e7ec51a160fe3cbfc82bc5853b2b75443b051bbeb52c98da19f609e9de0da18c341fe56a51b34f95203cb9bbab9fda0ba97eb5c43b3102911d6a68e05b8023efa4daeb8e217fd1c7325237d53f8e4e95fd3b0217dd13485a8f61f39478a21d55ec0a96ec0f163167053d86c76",
    "Timestamp" : "2016-08-17T07:14:09.912Z",
    "SignatureVersion" : "1",
    "Signature" : "Vzi/S+YKbWA7VfLMPJxiKoIEi61/kH3BHtRMFe3FdMAm6RcJyEUjVZ5CmJCRFywGspHcCP6db3JedeI9yLAKm9fwDDg74PanONzGhcb4ja3e7E7B7auCk7exAVZojrKbY+yEJk91CfoqY4BTp3m3sD2/9o1phj+Dn+hENDSGVRP3zrs6VCuL7KFPYi88kCT/5d3suHDpbINwCAkKkXZWcRtx+Ka7uZdq2AA6MJdedIQ+DscL+7C1htJ/X4LcUiw9KUsweibCbz1mxpZVJ9uLbW5uLmykkBjnp5SecRcYA5vqowGpMq/vyI8RANs9udnn0vnGYFh6GwHXFZbdZtDCsw==",
    "SigningCertURL" : "https://sns.eu-west-1.amazonaws.com/SimpleNotificationService-bb750dd426d95ee9390147a5624348ee.pem"
}
PAYLOAD;

        $amazonCallback = new AmazonCallback($this->translator, $this->logger, $this->mockHttp, $this->transportCallback);
        $request        = $this->createMock(Request::class);

        $request->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue($payload));

        // Mock a successful response
        $mockResponse = new Response(200);

        $this->mockHttp->expects($this->once())
            ->method('get')
            ->willReturn($mockResponse);

        $amazonCallback->processCallbackRequest($request);
    }

    public function testprocessNotificationBounceRequest()
    {
        $payload = <<< 'PAYLOAD'
{
    "Type" : "Notification",
    "MessageId" : "7c2d7069-7db3-53c8-87d0-20476a630fb6",
    "TopicArn" : "arn:aws:sns:eu-west-1:918057160339:55hubs-mautic-test",
    "Message" : "{\"notificationType\":\"Bounce\",\"bounce\":{\"bounceType\":\"Permanent\",\"bounceSubType\":\"General\",\"bouncedRecipients\":[{\"emailAddress\":\"nope@nope.com\",\"action\":\"failed\",\"status\":\"5.1.1\",\"diagnosticCode\":\"smtp; 550 5.1.1 <nope@nope.com>: Recipient address rejected: User unknown in virtual alias table\"}],\"timestamp\":\"2016-08-17T07:43:12.776Z\",\"feedbackId\":\"0102015697743d4c-619f1aa8-763f-4bea-8648-0b3bbdedd1ea-000000\",\"reportingMTA\":\"dsn; a4-24.smtp-out.eu-west-1.amazonses.com\"},\"mail\":{\"timestamp\":\"2016-08-17T07:43:11.000Z\",\"source\":\"admin@55hubs.ch\",\"sourceArn\":\"arn:aws:ses:eu-west-1:918057160339:identity/nope.com\",\"sendingAccountId\":\"918057160339\",\"messageId\":\"010201569774384f-81311784-10dd-48a8-921f-8316c145e64d-000000\",\"destination\":[\"nope@nope.com\"]}}",
    "Timestamp" : "2016-08-17T07:43:12.822Z",
    "SignatureVersion" : "1",
    "Signature" : "GNWnMWfKx1PPDjUstq2Ln13+AJWEK/Qo8YllYC7dGSlPhC5nClop5+vCj0CG2XN7aN41GhsJJ1e+F4IiRxm9v2wwua6BC3mtykrXEi8VeGy2HuetbF9bEeBEPbtbeIyIXJhdPDhbs4anPJwcEiN/toCoANoPWJ3jyVTOaUAxJb2oPTrvmjMxMpVE59sSo7Mz2+pQaUJl3ma0UgAC/lrYghi6n4cwlDTfbbIW+mbV7/d/5YN/tjL9/sD3DOuf+1PpFFTPsOVseZWV8PQ0/MWB2BOrKOKQyF7msLNX5iTkmsvRrbYULPvpbx32LsIxfNVFZJmsnTe2/6EGaAXf3TVPZA==",
    "SigningCertURL" : "https://sns.eu-west-1.amazonaws.com/SimpleNotificationService-bb750dd426d95ee9390147a5624348ee.pem",
    "UnsubscribeURL" : "https://sns.eu-west-1.amazonaws.com/?Action=Unsubscribe&SubscriptionArn=arn:aws:sns:eu-west-1:918057160339:nope:1cddd2a6-bfa8-4eb5-b2b2-a7833eb5db9b"
}
PAYLOAD;

        $amazonCallback = new AmazonCallback($this->translator, $this->logger, $this->mockHttp, $this->transportCallback);

        $request = $this->getMockBuilder(Request::class)
        ->disableOriginalConstructor()
        ->getMock();

        $request->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue($payload));

        // Mock a successful response
        $mockResponse       = $this->getMockBuilder(Response::class)->getMock();
        $mockResponse->code = 200;

        $this->transportCallback->expects($this->once())
            ->method('addFailureByAddress');

        $amazonCallback->processCallbackRequest($request);
    }

    public function testprocessNotificationComplaintRequest()
    {
        $payload = <<< 'PAYLOAD'
{
    "Type" : "Notification",
    "MessageId" : "7c2d7069-7db3-53c8-87d0-20476a630fb6",
    "TopicArn" : "arn:aws:sns:eu-west-1:918057160339:55hubs-mautic-test",
    "Message": "{\"notificationType\":\"Complaint\", \"complaint\":{ \"complainedRecipients\":[ { \"emailAddress\":\"richard@example.com\" } ], \"timestamp\":\"2016-01-27T14:59:38.237Z\", \"feedbackId\":\"0000013786031775-fea503bc-7497-49e1-881b-a0379bb037d3-000000\" } }",
    "Timestamp" : "2016-08-17T07:43:12.822Z",
    "SignatureVersion" : "1",
    "Signature" : "GNWnMWfKx1PPDjUstq2Ln13+AJWEK/Qo8YllYC7dGSlPhC5nClop5+vCj0CG2XN7aN41GhsJJ1e+F4IiRxm9v2wwua6BC3mtykrXEi8VeGy2HuetbF9bEeBEPbtbeIyIXJhdPDhbs4anPJwcEiN/toCoANoPWJ3jyVTOaUAxJb2oPTrvmjMxMpVE59sSo7Mz2+pQaUJl3ma0UgAC/lrYghi6n4cwlDTfbbIW+mbV7/d/5YN/tjL9/sD3DOuf+1PpFFTPsOVseZWV8PQ0/MWB2BOrKOKQyF7msLNX5iTkmsvRrbYULPvpbx32LsIxfNVFZJmsnTe2/6EGaAXf3TVPZA==",
    "SigningCertURL" : "https://sns.eu-west-1.amazonaws.com/SimpleNotificationService-bb750dd426d95ee9390147a5624348ee.pem",
    "UnsubscribeURL" : "https://sns.eu-west-1.amazonaws.com/?Action=Unsubscribe&SubscriptionArn=arn:aws:sns:eu-west-1:918057160339:nope:1cddd2a6-bfa8-4eb5-b2b2-a7833eb5db9b"
    }
PAYLOAD;

        $amazonCallback = new AmazonCallback($this->translator, $this->logger, $this->mockHttp, $this->transportCallback);

        $request = $this->getMockBuilder(Request::class)
        ->disableOriginalConstructor()
        ->getMock();

        $request->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue($payload));

        // Mock a successful response
        $mockResponse       = $this->getMockBuilder(Response::class)->getMock();
        $mockResponse->code = 200;

        $this->transportCallback->expects($this->once())
            ->method('addFailureByAddress');

        $amazonCallback->processCallbackRequest($request);
    }

    public function testprocessBounce()
    {
        $messageMock = $this->getMockBuilder(Message::class)
                        ->disableOriginalConstructor()
                        ->getMock();
        $messageMock->fromAddress = 'no-reply@sns.amazonaws.com';
        $messageMock->textPlain   = '{"notificationType":"Bounce","bounce":{"bounceType":"Permanent","bounceSubType":"General","bouncedRecipients":[{"emailAddress":"nope@nope.com","action":"failed","status":"5.1.1","diagnosticCode":"smtp; 550 5.1.1 <nope@nope.com>: Recipient address rejected: User unknown in virtual alias table"}],"timestamp":"2016-08-17T07:43:12.776Z","feedbackId":"0102015697743d4c-619f1aa8-763f-4bea-8648-0b3bbdedd1ea-000000","reportingMTA":"dsn; a4-24.smtp-out.eu-west-1.amazonses.com"},"mail":{"timestamp":"2016-08-17T07:43:11.000Z","source":"admin@55hubs.ch","sourceArn":"arn:aws:ses:eu-west-1:918057160339:identity/nope.com","sendingAccountId":"918057160339","messageId":"010201569774384f-81311784-10dd-48a8-921f-8316c145e64d-000000","destination":["nope@nope.com"]}}';
        $amazonCallback           = new AmazonCallback($this->translator, $this->logger, $this->mockHttp, $this->transportCallback);
        $bounce                   = new BouncedEmail();
        $bounce->setContactEmail('nope@nope.com')
            ->setBounceAddress('admin@55hubs.ch')
            ->setType('unknown')
            ->setRuleCategory('unknown')
            ->setRuleNumber('0013')
            ->setIsFinal(true);

        $this->assertEquals($bounce, $amazonCallback->processBounce($messageMock));
    }

    public function testprocessUnsubscription()
    {
        $messageMock = $this->getMockBuilder(Message::class)
                        ->disableOriginalConstructor()
                        ->getMock();
        $messageMock->fromAddress = 'no-reply@sns.amazonaws.com';
        $messageMock->textPlain   = '{"notificationType":"Complaint", "complaint":{ "complainedRecipients":[ { "emailAddress":"nope@nope.com" } ], "timestamp":"2016-01-27T14:59:38.237Z", "feedbackId":"0000013786031775-fea503bc-7497-49e1-881b-a0379bb037d3-000000" }, "mail":{"source": "unknown"} }';
        $amazonCallback           = new AmazonCallback($this->translator, $this->logger, $this->mockHttp, $this->transportCallback);
        $unsubscribe              = new UnsubscribedEmail('nope@nope.com', 'unknown');
        $this->assertEquals($unsubscribe, $amazonCallback->processUnsubscription($messageMock));
    }

    public function testprocessNotificationBounceRequestConfigSet()
    {
        $payload = <<< 'PAYLOAD'
        {"eventType":"Bounce","bounce":{"bounceType":"Permanent","bounceSubType":"General","bouncedRecipients":[{"emailAddress":"recipient@example.com","action":"failed","status":"5.1.1","diagnosticCode":"smtp; 550 5.1.1 user unknown"}],"timestamp":"2017-08-05T00:41:02.669Z","feedbackId":"01000157c44f053b-61b59c11-9236-11e6-8f96-7be8aexample-000000","reportingMTA":"dsn; mta.example.com"},"mail":{"timestamp":"2017-08-05T00:40:02.012Z","source":"Sender Name <sender@example.com>","sourceArn":"arn:aws:ses:us-east-1:123456789012:identity/sender@example.com","sendingAccountId":"123456789012","messageId":"EXAMPLE7c191be45-e9aedb9a-02f9-4d12-a87d-dd0099a07f8a-000000","destination":["recipient@example.com"],"headersTruncated":false,"headers":[{"name":"From","value":"Sender Name <sender@example.com>"},{"name":"To","value":"recipient@example.com"},{"name":"Subject","value":"Message sent from Amazon SES"},{"name":"MIME-Version","value":"1.0"},{"name":"Content-Type","value":"multipart/alternative; boundary=\"----=_Part_7307378_1629847660.1516840721503\""}],"commonHeaders":{"from":["Sender Name <sender@example.com>"],"to":["recipient@example.com"],"messageId":"EXAMPLE7c191be45-e9aedb9a-02f9-4d12-a87d-dd0099a07f8a-000000","subject":"Message sent from Amazon SES"},"tags":{"ses:configuration-set":["ConfigSet"],"ses:source-ip":["192.0.2.0"],"ses:from-domain":["example.com"],"ses:caller-identity":["ses_user"]}}}
PAYLOAD;

        $amazonCallback = new AmazonCallback($this->translator, $this->logger, $this->mockHttp, $this->transportCallback);

        $request = $this->getMockBuilder(Request::class)
        ->disableOriginalConstructor()
        ->getMock();

        $request->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue($payload));

        // Mock a successful response
        $mockResponse       = $this->getMockBuilder(Response::class)->getMock();
        $mockResponse->code = 200;

        $this->transportCallback->expects($this->once())
            ->method('addFailureByAddress');

        $amazonCallback->processCallbackRequest($request);
    }

    public function testprocessNotificationComplaintRequestConfigSet()
    {
        $payload = <<< 'PAYLOAD'
        {"eventType":"Complaint","complaint":{"complainedRecipients":[{"emailAddress":"recipient@example.com"}],"timestamp":"2017-08-05T00:41:02.669Z","feedbackId":"01000157c44f053b-61b59c11-9236-11e6-8f96-7be8aexample-000000","userAgent":"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36","complaintFeedbackType":"abuse","arrivalDate":"2017-08-05T00:41:02.669Z"},"mail":{"timestamp":"2017-08-05T00:40:01.123Z","source":"Sender Name <sender@example.com>","sourceArn":"arn:aws:ses:us-east-1:123456789012:identity/sender@example.com","sendingAccountId":"123456789012","messageId":"EXAMPLE7c191be45-e9aedb9a-02f9-4d12-a87d-dd0099a07f8a-000000","destination":["recipient@example.com"],"headersTruncated":false,"headers":[{"name":"From","value":"Sender Name <sender@example.com>"},{"name":"To","value":"recipient@example.com"},{"name":"Subject","value":"Message sent from Amazon SES"},{"name":"MIME-Version","value":"1.0"},{"name":"Content-Type","value":"multipart/alternative; boundary=\"----=_Part_7298998_679725522.1516840859643\""}],"commonHeaders":{"from":["Sender Name <sender@example.com>"],"to":["recipient@example.com"],"messageId":"EXAMPLE7c191be45-e9aedb9a-02f9-4d12-a87d-dd0099a07f8a-000000","subject":"Message sent from Amazon SES"},"tags":{"ses:configuration-set":["ConfigSet"],"ses:source-ip":["192.0.2.0"],"ses:from-domain":["example.com"],"ses:caller-identity":["ses_user"]}}}
PAYLOAD;

        $amazonCallback = new AmazonCallback($this->translator, $this->logger, $this->mockHttp, $this->transportCallback);

        $request = $this->getMockBuilder(Request::class)
        ->disableOriginalConstructor()
        ->getMock();

        $request->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue($payload));

        // Mock a successful response
        $mockResponse       = $this->getMockBuilder(Response::class)->getMock();
        $mockResponse->code = 200;

        $this->transportCallback->expects($this->once())
            ->method('addFailureByAddress');

        $amazonCallback->processCallbackRequest($request);
    }

    public function testprocessBounceConfigSet()
    {
        $messageMock = $this->getMockBuilder(Message::class)
                        ->disableOriginalConstructor()
                        ->getMock();
        $messageMock->fromAddress = 'no-reply@sns.amazonaws.com';
        $messageMock->textPlain   = '{"eventType":"Bounce","bounce":{"bounceType":"Permanent","bounceSubType":"General","bouncedRecipients":[{"emailAddress":"nope@nope.com","action":"failed","status":"5.1.1","diagnosticCode":"smtp; 550 5.1.1 <nope@nope.com>: Recipient address rejected: User unknown in virtual alias table"}],"timestamp":"2016-08-17T07:43:12.776Z","feedbackId":"0102015697743d4c-619f1aa8-763f-4bea-8648-0b3bbdedd1ea-000000","reportingMTA":"dsn; a4-24.smtp-out.eu-west-1.amazonses.com"},"mail":{"timestamp":"2016-08-17T07:43:11.000Z","source":"admin@55hubs.ch","sourceArn":"arn:aws:ses:eu-west-1:918057160339:identity/nope.com","sendingAccountId":"918057160339","messageId":"010201569774384f-81311784-10dd-48a8-921f-8316c145e64d-000000","destination":["nope@nope.com"]}}';
        $amazonCallback           = new AmazonCallback($this->translator, $this->logger, $this->mockHttp, $this->transportCallback);
        $bounce                   = new BouncedEmail();
        $bounce->setContactEmail('nope@nope.com')
            ->setBounceAddress('admin@55hubs.ch')
            ->setType('unknown')
            ->setRuleCategory('unknown')
            ->setRuleNumber('0013')
            ->setIsFinal(true);

        $this->assertEquals($bounce, $amazonCallback->processBounce($messageMock));
    }

    public function testprocessUnsubscriptionConfigSet()
    {
        $messageMock = $this->getMockBuilder(Message::class)
                        ->disableOriginalConstructor()
                        ->getMock();
        $messageMock->fromAddress = 'no-reply@sns.amazonaws.com';
        $messageMock->textPlain   = '{"eventType":"Complaint", "complaint":{ "complainedRecipients":[ { "emailAddress":"nope@nope.com" } ], "timestamp":"2016-01-27T14:59:38.237Z", "feedbackId":"0000013786031775-fea503bc-7497-49e1-881b-a0379bb037d3-000000" }, "mail":{"source": "unknown"} }';
        $amazonCallback           = new AmazonCallback($this->translator, $this->logger, $this->mockHttp, $this->transportCallback);
        $unsubscribe              = new UnsubscribedEmail('nope@nope.com', 'unknown');
        $this->assertEquals($unsubscribe, $amazonCallback->processUnsubscription($messageMock));
    }
}
