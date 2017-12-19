<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Transport;

use Joomla\Http\Http;
use Mautic\EmailBundle\Swiftmailer\Transport\AmazonTransport;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class AmazonTransportTest.
 */
class AmazonTransportTest extends KernelTestCase
{
    private $container;

    public function setUp()
    {
        self::bootKernel();

        $this->container = self::$kernel->getContainer();
    }

    /**
     * Test that the confirmation URL from the payload is called.
     */
    public function testConfirmationCallbackSuccessfull()
    {
        $logger     = $this->container->get('logger');
        $translator = $this->container->get('translator');

        // Mock http connector
        $mockHttp = $this->getMockBuilder('Joomla\Http\Http')
            ->disableOriginalConstructor()
            ->getMock();

        // Mock a successful response
        $mockResponse = $this->getMockBuilder('Joomla\Http\Response')
            ->getMock();
        $mockResponse->code = 200;

        $mockHttp->expects($this->once())
            ->method('get')
            ->willReturn($mockResponse);

        // payload which is sent by Amazon SES
        $payload = <<<'PAYLOAD'
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

        $jsonPayload = json_decode($payload, true);

        $transport = new AmazonTransport('localhost', $mockHttp);
        $transport->processJsonPayload($jsonPayload, $logger, $translator);
    }

    /**
     * Test that a bounce message is properly processed by the mailer callback.
     */
    public function testSingleBounceCallbackSuccessfull()
    {
        $logger     = $this->container->get('logger');
        $translator = $this->container->get('translator');

        // payload which is sent by Amazon SES
        $payload = <<<PAYLOAD
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

        $jsonPayload = json_decode($payload, true);

        $transport = new AmazonTransport('localhost', new Http());
        $rows      = $transport->processJsonPayload($jsonPayload, $logger, $translator);

        $this->assertArrayHasKey('nope@nope.com', $rows[2]['emails']);
        $this->assertContains('Recipient address rejected', $rows[2]['emails']['nope@nope.com']);
    }

    /**
     * Test that a complaint message without a feedback report is processed with the reason "unknown".
     */
    public function testSingleComplaintWithoutFeedbackCallbackSuccessfull()
    {
        $logger     = $this->container->get('logger');
        $translator = $this->container->get('translator');

        // payload which is sent by Amazon SES
        $payload = <<<PAYLOAD
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

        $jsonPayload = json_decode($payload, true);

        $transport = new AmazonTransport('localhost', new Http());
        $rows      = $transport->processJsonPayload($jsonPayload, $logger, $translator);

        $this->assertArrayHasKey('richard@example.com', $rows[1]['emails']);
        $this->assertEquals($translator->trans('mautic.email.complaint.reason.unknown'), $rows[1]['emails']['richard@example.com']);
    }

    /**
     * Test that a complaint message with a feedback is processed and the reason is set accordingly.
     */
    public function testSingleComplaintWithFeedbackCallbackSuccessfull()
    {
        $logger     = $this->container->get('logger');
        $translator = $this->container->get('translator');

        // payload which is sent by Amazon SES
        $payload = <<<PAYLOAD
{
  "Type" : "Notification",
  "MessageId" : "7c2d7069-7db3-53c8-87d0-20476a630fb6",
  "TopicArn" : "arn:aws:sns:eu-west-1:918057160339:55hubs-mautic-test",
  "Message": "{ \"notificationType\":\"Complaint\", \"complaint\":{ \"userAgent\":\"AnyCompany Feedback Loop (V0.01)\", \"complainedRecipients\":[ { \"emailAddress\":\"richard@example.com\" } ], \"complaintFeedbackType\":\"abuse\", \"arrivalDate\":\"2016-01-27T14:59:38.237Z\", \"timestamp\":\"2016-01-27T14:59:38.237Z\", \"feedbackId\":\"000001378603177f-18c07c78-fa81-4a58-9dd1-fedc3cb8f49a-000000\" }}",
  "Timestamp" : "2016-08-17T07:43:12.822Z",
  "SignatureVersion" : "1",
  "Signature" : "GNWnMWfKx1PPDjUstq2Ln13+AJWEK/Qo8YllYC7dGSlPhC5nClop5+vCj0CG2XN7aN41GhsJJ1e+F4IiRxm9v2wwua6BC3mtykrXEi8VeGy2HuetbF9bEeBEPbtbeIyIXJhdPDhbs4anPJwcEiN/toCoANoPWJ3jyVTOaUAxJb2oPTrvmjMxMpVE59sSo7Mz2+pQaUJl3ma0UgAC/lrYghi6n4cwlDTfbbIW+mbV7/d/5YN/tjL9/sD3DOuf+1PpFFTPsOVseZWV8PQ0/MWB2BOrKOKQyF7msLNX5iTkmsvRrbYULPvpbx32LsIxfNVFZJmsnTe2/6EGaAXf3TVPZA==",
  "SigningCertURL" : "https://sns.eu-west-1.amazonaws.com/SimpleNotificationService-bb750dd426d95ee9390147a5624348ee.pem",
  "UnsubscribeURL" : "https://sns.eu-west-1.amazonaws.com/?Action=Unsubscribe&SubscriptionArn=arn:aws:sns:eu-west-1:918057160339:nope:1cddd2a6-bfa8-4eb5-b2b2-a7833eb5db9b"
}
PAYLOAD;

        $jsonPayload = json_decode($payload, true);

        $transport = new AmazonTransport('localhost', new Http());
        $rows      = $transport->processJsonPayload($jsonPayload, $logger, $translator);

        $this->assertArrayHasKey('richard@example.com', $rows[1]['emails']);
        $this->assertEquals($translator->trans('mautic.email.complaint.reason.abuse'), $rows[1]['emails']['richard@example.com']);
    }
}
