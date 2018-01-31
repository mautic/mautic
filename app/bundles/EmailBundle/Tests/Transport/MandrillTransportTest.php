<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Transport;

use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Model\TransportCallback;
use Mautic\EmailBundle\Swiftmailer\Transport\MandrillTransport;
use Mautic\LeadBundle\Entity\DoNotContact;
use Symfony\Component\HttpFoundation\Request;

class MandrillTransportTest extends \PHPUnit_Framework_TestCase
{
    public function testWebhookPayloadIsProcessed()
    {
        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $translator->method('trans')
            ->willReturnCallback(
                function ($key) {
                    return $key;
                }
            );

        $transportCallback = $this->getMockBuilder(TransportCallback::class)
            ->disableOriginalConstructor()
            ->getMock();

        $transportCallback->expects($this->exactly(4))
            ->method('addFailureByHashId')
            ->withConsecutive(
                [$this->equalTo('1'), "smtp;550 5.1.1 The email account that you tried to reach does not exist. Please try double-checking the recipient's email address for typos or unnecessary spaces.", DoNotContact::BOUNCED],
                [$this->equalTo('2'), 'unsubscribed', DoNotContact::UNSUBSCRIBED],
                [$this->equalTo('3'), 'unsubscribed', DoNotContact::UNSUBSCRIBED],
                [$this->equalTo('4'), 'reject', DoNotContact::BOUNCED]
            );

        $transportCallback->expects($this->once())
            ->method('addFailureByAddress')
            ->with(
                'bounce@mandrill.test',
                'reject',
                DoNotContact::BOUNCED
            );

        $mandrill = new MandrillTransport($translator, $transportCallback);

        $mandrill->processCallbackRequest($this->getRequestWithPayload());
    }

    /**
     * @return Request
     */
    private function getRequestWithPayload()
    {
        $json = <<<JSON
    [
       {
          "event":"hard_bounce",
          "msg":{
             "ts":1365109999,
             "subject":"This an example webhook message",
             "email":"example.webhook@mandrillapp.com",
             "sender":"example.sender@mandrillapp.com",
             "tags":[
                "webhook-example"
             ],
             "state":"bounced",
             "metadata":{
                "hashId":1
             },
             "_id":"exampleaaaaaaaaaaaaaaaaaaaaaaaaa",
             "_version":"exampleaaaaaaaaaaaaaaa",
             "bounce_description":"bad_mailbox",
             "bgtools_code":10,
             "diag":"smtp;550 5.1.1 The email account that you tried to reach does not exist. Please try double-checking the recipient's email address for typos or unnecessary spaces."
          },
          "_id":"exampleaaaaaaaaaaaaaaaaaaaaaaaaa",
          "ts":1513974230
       },
       {
          "event":"spam",
          "msg":{
             "ts":1365109999,
             "subject":"This an example webhook message",
             "email":"example.webhook@mandrillapp.com",
             "sender":"example.sender@mandrillapp.com",
             "tags":[
                "webhook-example"
             ],
             "opens":[
                {
                   "ts":1365111111
                }
             ],
             "clicks":[
                {
                   "ts":1365111111,
                   "url":"http:\/\/mandrill.com"
                }
             ],
             "state":"sent",
             "metadata":{
                "hashId":2
             },
             "_id":"exampleaaaaaaaaaaaaaaaaaaaaaaaaa1",
             "_version":"exampleaaaaaaaaaaaaaaa"
          },
          "_id":"exampleaaaaaaaaaaaaaaaaaaaaaaaaa1",
          "ts":1513974230
       },
       {
          "event":"unsub",
          "msg":{
             "ts":1365109999,
             "subject":"This an example webhook message",
             "email":"example.webhook@mandrillapp.com",
             "sender":"example.sender@mandrillapp.com",
             "tags":[
                "webhook-example"
             ],
             "opens":[
                {
                   "ts":1365111111
                }
             ],
             "clicks":[
                {
                   "ts":1365111111,
                   "url":"http:\/\/mandrill.com"
                }
             ],
             "state":"sent",
             "metadata":{
                "hashId":3
             },
             "_id":"exampleaaaaaaaaaaaaaaaaaaaaaaaaa2",
             "_version":"exampleaaaaaaaaaaaaaaa"
          },
          "_id":"exampleaaaaaaaaaaaaaaaaaaaaaaaaa2",
          "ts":1513974230
       },
       {
          "event":"reject",
          "msg":{
             "ts":1365109999,
             "subject":"This an example webhook message",
             "email":"example.webhook@mandrillapp.com",
             "sender":"example.sender@mandrillapp.com",
             "tags":[
                "webhook-example"
             ],
             "opens":[
    
             ],
             "clicks":[
    
             ],
             "state":"rejected",
             "metadata":{
                "hashId":4
             },
             "_id":"exampleaaaaaaaaaaaaaaaaaaaaaaaaa3",
             "_version":"exampleaaaaaaaaaaaaaaa"
          },
          "_id":"exampleaaaaaaaaaaaaaaaaaaaaaaaaa3",
          "ts":1513974230
       },
       {
          "event":"reject",
          "msg":{
             "ts":1365109999,
             "subject":"This an example webhook message",
             "email":"bounce@mandrill.test",
             "sender":"example.sender@mandrillapp.com",
             "tags":[
                "webhook-example"
             ],
             "opens":[
    
             ],
             "clicks":[
    
             ],
             "state":"rejected",
             "metadata":{
                "custom":1
             },
             "_id":"exampleaaaaaaaaaaaaaaaaaaaaaaaaa3",
             "_version":"exampleaaaaaaaaaaaaaaa"
          },
          "_id":"exampleaaaaaaaaaaaaaaaaaaaaaaaaa3",
          "ts":1513974230
       }
    ]
JSON;

        return new Request([], ['mandrill_events' => $json]);
    }
}
