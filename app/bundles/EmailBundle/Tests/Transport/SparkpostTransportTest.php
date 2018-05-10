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
use Mautic\EmailBundle\Swiftmailer\Transport\SparkpostTransport;
use Mautic\LeadBundle\Entity\DoNotContact;
use Symfony\Component\HttpFoundation\Request;

class SparkpostTransportTest extends \PHPUnit_Framework_TestCase
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

        $transportCallback->expects($this->exactly(6))
            ->method('addFailureByHashId')
            ->withConsecutive(
                [$this->equalTo('1'), 'MAIL REFUSED - IP (17.99.99.99) is in black list', DoNotContact::BOUNCED],
                [$this->equalTo('2'), 'abuse', DoNotContact::UNSUBSCRIBED],
                [$this->equalTo('3'), 'MAIL REFUSED - IP (18.99.99.99) is in black list', DoNotContact::BOUNCED],
                [$this->equalTo('4'), 'MAIL REFUSED - IP (19.99.99.99) is in black list', DoNotContact::BOUNCED],
                [$this->equalTo('5'), 'unsubscribed', DoNotContact::UNSUBSCRIBED],
                [$this->equalTo('6'), 'unsubscribed', DoNotContact::UNSUBSCRIBED]
                // cc recipient type is ignored so addFailureByHashId should not be called
        );

        $transportCallback->expects($this->once())
            ->method('addFailureByAddress')
            ->with(
                'bounce@example.com',
                'MAIL REFUSED - IP (17.99.99.99) is in black list',
                DoNotContact::BOUNCED
            );

        $sparkpost = new SparkpostTransport('1234', $translator, $transportCallback);

        $sparkpost->processCallbackRequest($this->getRequestWithPayload());
    }

    private function getRequestWithPayload()
    {
        $json = <<<JSON
[
    {
      "msys": {
        "message_event": {
          "type": "bounce",
          "bounce_class": "10",
          "campaign_id": "Example Campaign Name",
          "customer_id": "1",
          "delv_method": "esmtp",
          "device_token": "45c19189783f867973f6e6a5cca60061ffe4fa77c547150563a1192fa9847f8a",
          "error_code": "554",
          "event_id": "92356927693813856",
          "friendly_from": "sender@example.com",
          "ip_address": "127.0.0.1",
          "ip_pool": "Example-Ip-Pool",
          "message_id": "000443ee14578172be22",
          "msg_from": "sender@example.com",
          "msg_size": "1337",
          "num_retries": "2",
          "rcpt_meta": {
            "hashId": "1"
          },
          "rcpt_tags": [
            "male",
            "US"
          ],
          "rcpt_to": "recipient@example.com",
          "raw_rcpt_to": "recipient@example.com",
          "rcpt_type": "to",
          "raw_reason": "MAIL REFUSED - IP (17.99.99.99) is in black list",
          "reason": "MAIL REFUSED - IP (a.b.c.d) is in black list",
          "routing_domain": "example.com",
          "sending_ip": "127.0.0.1",
          "sms_coding": "ASCII",
          "sms_dst": "7876712656",
          "sms_dst_npi": "E164",
          "sms_dst_ton": "International",
          "sms_src": "1234",
          "sms_src_npi": "E164",
          "sms_src_ton": "Unknown",
          "subaccount_id": "101",
          "subject": "Summer deals are here!",
          "template_id": "templ-1234",
          "template_version": "1",
          "timestamp": "1454442600",
          "transmission_id": "65832150921904138"
        }
      }
    },
    {
      "msys": {
        "message_event": {
          "type": "spam_complaint",
          "campaign_id": "Example Campaign Name",
          "customer_id": "1",
          "delv_method": "esmtp",
          "event_id": "92356927693813856",
          "fbtype": "abuse",
          "friendly_from": "sender@example.com",
          "ip_address": "127.0.0.1",
          "ip_pool": "Example-Ip-Pool",
          "message_id": "000443ee14578172be22",
          "msg_from": "sender@example.com",
          "msg_size": "1337",
          "num_retries": "2",
          "queue_time": "12",
          "rcpt_meta": {
            "hashId": "2"
          },
          "rcpt_tags": [
            "male",
            "US"
          ],
          "rcpt_to": "recipient@example.com",
          "raw_rcpt_to": "recipient@example.com",
          "rcpt_type": "to",
          "report_by": "server.email.com",
          "report_to": "abuse.example.com",
          "routing_domain": "example.com",
          "sending_ip": "127.0.0.1",
          "subaccount_id": "101",
          "subject": "Summer deals are here!",
          "template_id": "templ-1234",
          "template_version": "1",
          "timestamp": "1454442600",
          "transmission_id": "65832150921904138",
          "user_str": "Additional Example Information"
        }
      }
    },
    {
      "msys": {
        "message_event": {
          "type": "out_of_band",
          "bounce_class": "1",
          "campaign_id": "Example Campaign Name",
          "customer_id": "1",
          "delv_method": "esmtp",
          "device_token": "45c19189783f867973f6e6a5cca60061ffe4fa77c547150563a1192fa9847f8a",
          "error_code": "554",
          "event_id": "92356927693813856",
          "friendly_from": "sender@example.com",
          "ip_address": "127.0.0.1",
          "ip_pool": "Example-Ip-Pool",
          "message_id": "000443ee14578172be22",
          "msg_from": "sender@example.com",
          "msg_size": "1337",
          "num_retries": "2",
          "queue_time": "12",
          "raw_rcpt_to": "recipient@example.com",
          "raw_reason": "MAIL REFUSED - IP (18.99.99.99) is in black list",
          "rcpt_meta": {
            "hashId": "3"
          },
          "rcpt_tags": [
            "male",
            "US"
          ],
          "rcpt_to": "recipient@example.com",
          "reason": "MAIL REFUSED - IP (a.b.c.d) is in black list",
          "routing_domain": "example.com",
          "sending_ip": "127.0.0.1",
          "subaccount_id": "101",
          "subject": "Summer deals are here!",
          "template_id": "templ-1234",
          "template_version": "1",
          "timestamp": "1454442600",
          "transmission_id": "65832150921904138"
        }
      }
    },
    {
      "msys": {
        "message_event": {
          "type": "policy_rejection",
          "campaign_id": "Example Campaign Name",
          "customer_id": "1",
          "error_code": "554",
          "event_id": "92356927693813856",
          "friendly_from": "sender@example.com",
          "message_id": "000443ee14578172be22",
          "msg_from": "sender@example.com",
          "rcpt_meta": {
            "hashId": "4"
          },
          "rcpt_tags": [
            "male",
            "US"
          ],
          "rcpt_to": "recipient@example.com",
          "raw_rcpt_to": "recipient@example.com",
          "rcpt_type": "to",
          "raw_reason": "MAIL REFUSED - IP (19.99.99.99) is in black list",
          "reason": "MAIL REFUSED - IP (a.b.c.d) is in black list",
          "remote_addr": "127.0.0.1",
          "subaccount_id": "101",
          "template_id": "templ-1234",
          "template_version": "1",
          "timestamp": "1454442600",
          "transmission_id": "65832150921904138",
          "bounce_class": "25"
        }
      }
    },
    {
      "msys": {
        "unsubscribe_event": {
          "type": "list_unsubscribe",
          "campaign_id": "Example Campaign Name",
          "customer_id": "1",
          "delv_method": "esmtp",
          "event_id": "92356927693813856",
          "friendly_from": "sender@example.com",
          "ip_address": "127.0.0.1",
          "ip_pool": "Example-Ip-Pool",
          "mailfrom": "recipient@example.com",
          "message_id": "000443ee14578172be22",
          "msg_from": "sender@example.com",
          "msg_size": "1337",
          "num_retries": "2",
          "queue_time": "12",
          "rcpt_meta": {
            "hashId": "5"
          },
          "rcpt_tags": [
            "male",
            "US"
          ],
          "rcpt_to": "recipient@example.com",
          "raw_rcpt_to": "recipient@example.com",
          "rcpt_type": "to",
          "routing_domain": "example.com",
          "sending_ip": "127.0.0.1",
          "subaccount_id": "101",
          "subject": "Summer deals are here!",
          "template_id": "templ-1234",
          "template_version": "1",
          "timestamp": "1454442600",
          "transmission_id": "65832150921904138"
        }
      }
    },
    {
      "msys": {
        "unsubscribe_event": {
          "type": "link_unsubscribe",
          "campaign_id": "Example Campaign Name",
          "customer_id": "1",
          "delv_method": "esmtp",
          "event_id": "92356927693813856",
          "friendly_from": "sender@example.com",
          "ip_address": "127.0.0.1",
          "ip_pool": "Example-Ip-Pool",
          "mailfrom": "recipient@example.com",
          "message_id": "000443ee14578172be22",
          "msg_from": "sender@example.com",
          "msg_size": "1337",
          "num_retries": "2",
          "queue_time": "12",
          "rcpt_meta": {
            "hashId": "6"
          },
          "rcpt_tags": [
            "male",
            "US"
          ],
          "rcpt_to": "recipient@example.com",
          "raw_rcpt_to": "recipient@example.com",
          "rcpt_type": "to",
          "routing_domain": "example.com",
          "sending_ip": "127.0.0.1",
          "subaccount_id": "101",
          "subject": "Summer deals are here!",
          "template_id": "templ-1234",
          "template_version": "1",
          "timestamp": "1454442600",
          "transmission_id": "65832150921904138",
          "user_agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36"
        }
      }
    },
    {
      "msys": {
        "message_event": {
          "type": "bounce",
          "bounce_class": "10",
          "campaign_id": "Example Campaign Name",
          "customer_id": "1",
          "delv_method": "esmtp",
          "device_token": "45c19189783f867973f6e6a5cca60061ffe4fa77c547150563a1192fa9847f8a",
          "error_code": "554",
          "event_id": "92356927693813856",
          "friendly_from": "sender@example.com",
          "ip_address": "127.0.0.1",
          "ip_pool": "Example-Ip-Pool",
          "message_id": "000443ee14578172be22",
          "msg_from": "sender@example.com",
          "msg_size": "1337",
          "num_retries": "2",
          "rcpt_meta": {
            "hashId": "7"
          },
          "rcpt_tags": [
            "male",
            "US"
          ],
          "rcpt_to": "recipient@example.com",
          "raw_rcpt_to": "recipient@example.com",
          "rcpt_type": "cc",
          "raw_reason": "MAIL REFUSED - IP (17.99.99.99) is in black list",
          "reason": "MAIL REFUSED - IP (a.b.c.d) is in black list",
          "routing_domain": "example.com",
          "sending_ip": "127.0.0.1",
          "sms_coding": "ASCII",
          "sms_dst": "7876712656",
          "sms_dst_npi": "E164",
          "sms_dst_ton": "International",
          "sms_src": "1234",
          "sms_src_npi": "E164",
          "sms_src_ton": "Unknown",
          "subaccount_id": "101",
          "subject": "Summer deals are here!",
          "template_id": "templ-1234",
          "template_version": "1",
          "timestamp": "1454442600",
          "transmission_id": "65832150921904138"
        }
      }
    },
    {
      "msys": {
        "message_event": {
          "type": "bounce",
          "bounce_class": "10",
          "campaign_id": "Example Campaign Name",
          "customer_id": "1",
          "delv_method": "esmtp",
          "device_token": "45c19189783f867973f6e6a5cca60061ffe4fa77c547150563a1192fa9847f8a",
          "error_code": "554",
          "event_id": "92356927693813856",
          "friendly_from": "sender@example.com",
          "ip_address": "127.0.0.1",
          "ip_pool": "Example-Ip-Pool",
          "message_id": "000443ee14578172be22",
          "msg_from": "sender@example.com",
          "msg_size": "1337",
          "num_retries": "2",
          "rcpt_meta": {
            "customField": "customValue"
          },
          "rcpt_tags": [
            "male",
            "US"
          ],
          "rcpt_to": "recipient@example.com",
          "raw_rcpt_to": "recipient@example.com",
          "rcpt_type": "cc",
          "raw_reason": "MAIL REFUSED - IP (17.99.99.99) is in black list",
          "reason": "MAIL REFUSED - IP (a.b.c.d) is in black list",
          "routing_domain": "example.com",
          "sending_ip": "127.0.0.1",
          "sms_coding": "ASCII",
          "sms_dst": "7876712656",
          "sms_dst_npi": "E164",
          "sms_dst_ton": "International",
          "sms_src": "1234",
          "sms_src_npi": "E164",
          "sms_src_ton": "Unknown",
          "subaccount_id": "101",
          "subject": "Summer deals are here!",
          "template_id": "templ-1234",
          "template_version": "1",
          "timestamp": "1454442600",
          "transmission_id": "65832150921904138"
        }
      }
    },
    {
      "msys": {
        "message_event": {
          "type": "bounce",
          "bounce_class": "10",
          "campaign_id": "Example Campaign Name",
          "customer_id": "1",
          "delv_method": "esmtp",
          "device_token": "45c19189783f867973f6e6a5cca60061ffe4fa77c547150563a1192fa9847f8a",
          "error_code": "554",
          "event_id": "92356927693813856",
          "friendly_from": "sender@example.com",
          "ip_address": "127.0.0.1",
          "ip_pool": "Example-Ip-Pool",
          "message_id": "000443ee14578172be22",
          "msg_from": "sender@example.com",
          "msg_size": "1337",
          "num_retries": "2",
          "rcpt_meta": {
            "customField": "customValue"
          },
          "rcpt_tags": [
            "male",
            "US"
          ],
          "rcpt_to": "bounce@example.com",
          "raw_rcpt_to": "recipient@example.com",
          "rcpt_type": "to",
          "raw_reason": "MAIL REFUSED - IP (17.99.99.99) is in black list",
          "reason": "MAIL REFUSED - IP (a.b.c.d) is in black list",
          "routing_domain": "example.com",
          "sending_ip": "127.0.0.1",
          "sms_coding": "ASCII",
          "sms_dst": "7876712656",
          "sms_dst_npi": "E164",
          "sms_dst_ton": "International",
          "sms_src": "1234",
          "sms_src_npi": "E164",
          "sms_src_ton": "Unknown",
          "subaccount_id": "101",
          "subject": "Summer deals are here!",
          "template_id": "templ-1234",
          "template_version": "1",
          "timestamp": "1454442600",
          "transmission_id": "65832150921904138"
        }
      }
    }
]
JSON;

        return new Request([], json_decode($json, true));
    }
}
