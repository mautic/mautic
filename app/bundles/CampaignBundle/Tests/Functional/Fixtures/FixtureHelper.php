<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Fixtures;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\LeadBundle\Entity\Lead;

final class FixtureHelper
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function createContact(string $email): Lead
    {
        $contact = new Lead();
        $contact->setEmail($email);

        $this->em->persist($contact);

        return $contact;
    }

    public function addContactToCampaign(Lead $contact, Campaign $campaign): CampaignLead
    {
        $ref = new CampaignLead();
        $ref->setCampaign($campaign);
        $ref->setLead($contact);
        $ref->setDateAdded(new \DateTime());

        $this->em->persist($ref);

        return $ref;
    }

    public function createCampaign(string $name): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName($name);
        $campaign->setIsPublished(true);

        $this->em->persist($campaign);

        return $campaign;
    }

    public function createCampaignWithScheduledEvent(Campaign $campaign, int $interval = 1, string $intervalUnit = 'd', \DateTimeInterface $hour = null): Event
    {
        if (!$campaign->getId()) {
            $this->em->flush();
        }

        $event = new Event();
        $event->setCampaign($campaign);
        $event->setName('Adjust contact points');
        $event->setType('lead.changepoints');
        $event->setEventType('action');
        $event->setTriggerInterval($interval);
        $event->setTriggerIntervalUnit($intervalUnit);
        $event->setTriggerMode('interval');
        if ($hour) {
            $event->setTriggerHour($hour->format('H:i'));
        }
        $event->setProperties(
            [
                'canvasSettings' => [
                    'droppedX' => '1080',
                    'droppedY' => '155',
                ],
                'name'                       => '',
                'triggerMode'                => 'interval',
                'triggerDate'                => null,
                'triggerInterval'            => $interval,
                'triggerIntervalUnit'        => $intervalUnit,
                'triggerHour'                => $hour,
                'triggerRestrictedStartHour' => '',
                'triggerRestrictedStopHour'  => '',
                'anchor'                     => 'leadsource',
                'properties'                 => ['points' => '5'],
                'type'                       => 'lead.changepoints',
                'eventType'                  => 'action',
                'anchorEventType'            => 'source',
                'campaignId'                 => $campaign->getId(),
                'buttons'                    => ['save' => ''],
                'points'                     => 5,
            ]
        );

        $this->em->persist($event);
        $this->em->flush();

        $campaign->addEvent(0, $event);
        $campaign->setCanvasSettings(
            [
                'nodes' => [
                    [
                        'id'        => $event->getId(),
                        'positionX' => '1080',
                        'positionY' => '155',
                    ],
                    [
                        'id'        => 'lists',
                        'positionX' => '1180',
                        'positionY' => '50',
                    ],
                ],
                'connections' => [
                    [
                        'sourceId' => 'lists',
                        'targetId' => $event->getId(),
                        'anchors'  => [
                            'source' => 'leadsource',
                            'target' => 'top',
                        ],
                    ],
                ],
            ]
        );

        return $event;
    }

    /**
     * Creates campaign with email sent action.
     *
     * Campaign diagram:
     * -------------------
     * -  Start segment  -
     * -------------------
     *         |
     * -------------------
     * -   Send email    -
     * -------------------
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createCampaignWithEmailSent(int $emailId): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Test send email');

        $this->em->persist($campaign);
        $this->em->flush();

        $event1 = new Event();
        $event1->setCampaign($campaign);
        $event1->setName('Send email');
        $event1->setType('email.send');
        $event1->setChannel('email');
        $event1->setChannelId($emailId);
        $event1->setEventType('action');
        $event1->setTriggerMode('immediate');
        $event1->setOrder(1);
        $event1->setProperties(
            [
                'canvasSettings' => [
                    'droppedX' => '549',
                    'droppedY' => '155',
                ],
                'name'                       => '',
                'triggerMode'                => 'immediate',
                'triggerDate'                => null,
                'triggerInterval'            => '1',
                'triggerIntervalUnit'        => 'd',
                'triggerHour'                => '',
                'triggerRestrictedStartHour' => '',
                'triggerRestrictedStopHour'  => '',
                'anchor'                     => 'leadsource',
                'properties'                 => [
                    'email'      => $emailId,
                    'email_type' => 'transactional',
                    'priority'   => '2',
                    'attempts'   => '3',
                ],
                'type'            => 'email.send',
                'eventType'       => 'action',
                'anchorEventType' => 'source',
                'campaignId'      => 'mautic_ce6c7dddf8444e579d741c0125f18b33a5d49b45',
                '_token'          => 'HgysZwvH_n0uAp47CcAcsGddRnRk65t-3crOnuLx28Y',
                'buttons'         => [
                    'save' => '',
                ],
                'email'      => $emailId,
                'email_type' => 'transactional',
                'priority'   => 2,
                'attempts'   => 3.0,
            ]
        );
        $this->em->persist($event1);
        $this->em->flush();

        $campaign->setCanvasSettings(
            [
                'nodes'       => [
                    [
                        'id'        => $event1->getId(),
                        'positionX' => '549',
                        'positionY' => '155',
                    ],
                    [
                        'id'        => 'lists',
                        'positionX' => '796',
                        'positionY' => '50',
                    ],
                ],
                'connections' => [
                    [
                        'sourceId' => 'lists',
                        'targetId' => $event1->getId(),
                        'anchors'  => [
                            'source' => 'leadsource',
                            'target' => 'top',
                        ],
                    ],
                ],
            ]
        );
        $campaign->addEvent($event1->getId(), $event1);
        $this->em->persist($campaign);
        $this->em->flush();

        return $campaign;
    }

    /** @return array<int, array<string, mixed>> */
    public static function getPayload(): array
    {
        $data = <<<JSON
        [
            {
                "campaign": [
                    {
                        "id": 3,
                        "name": "test2",
                        "description": null,
                        "is_published": false,
                        "canvas_settings": {
                            "nodes": [
                                {
                                    "id": "5",
                                    "positionX": "379",
                                    "positionY": "159"
                                },
                                {
                                    "id": "7",
                                    "positionX": "772",
                                    "positionY": "155"
                                },
                                {
                                    "id": "8",
                                    "positionX": "73",
                                    "positionY": "158"
                                },
                                {
                                    "id": "9",
                                    "positionX": "506",
                                    "positionY": "408"
                                },
                                {
                                    "id": "6",
                                    "positionX": "213",
                                    "positionY": "263"
                                },
                                {
                                    "id": "lists",
                                    "positionX": "653",
                                    "positionY": "53"
                                },
                                {
                                    "id": "forms",
                                    "positionX": "947",
                                    "positionY": "51"
                                }
                            ],
                            "connections": [
                                {
                                    "sourceId": "lists",
                                    "targetId": "5",
                                    "anchors": {
                                        "source": "leadsource",
                                        "target": "top"
                                    }
                                },
                                {
                                    "sourceId": "5",
                                    "targetId": "6",
                                    "anchors": {
                                        "source": "yes",
                                        "target": "top"
                                    }
                                },
                                {
                                    "sourceId": "lists",
                                    "targetId": "7",
                                    "anchors": {
                                        "source": "leadsource",
                                        "target": "top"
                                    }
                                },
                                {
                                    "sourceId": "lists",
                                    "targetId": "8",
                                    "anchors": {
                                        "source": "leadsource",
                                        "target": "top"
                                    }
                                },
                                {
                                    "sourceId": "lists",
                                    "targetId": "9",
                                    "anchors": {
                                        "source": "leadsource",
                                        "target": "top"
                                    }
                                },
                                {
                                    "sourceId": "lists",
                                    "targetId": "forms",
                                    "anchors": {
                                        "source": "leadsourceright",
                                        "target": "leadsourceleft"
                                    }
                                }
                            ]
                        },
                        "uuid": "b4ddc4d7-149e-4a81-9141-0e03c598627a"
                    }
                ],
                "campaign_event": [
                    {
                        "id": 5,
                        "campaign_id": 3,
                        "name": "Downloads asset",
                        "description": "",
                        "type": "asset.download",
                        "event_type": "decision",
                        "event_order": 1,
                        "properties": {
                            "canvasSettings": {
                                "droppedX": "313",
                                "droppedY": "158"
                            },
                            "name": "",
                            "anchor": "leadsource",
                            "properties": {
                                "assets": [
                                    2
                                ]
                            },
                            "type": "asset.download",
                            "eventType": "decision",
                            "anchorEventType": "source",
                            "campaignId": "3",
                            "_token": "0.oKNciNUEGsLHNnAw81-PkskwW94qK_8ClWqMXITDlz4.k5ZuwZo1VY6kZz5jvS-4-bNUD4h5ToVr3wHOa-OHp2mQ0xHfozNsiPIAGw",
                            "buttons": {
                                "save": ""
                            },
                            "triggerDate": null,
                            "assets": [
                                2
                            ]
                        },
                        "trigger_interval": 0,
                        "trigger_interval_unit": "",
                        "trigger_mode": "",
                        "triggerDate": null,
                        "channel": "asset",
                        "channel_id": 2,
                        "parent_id": null,
                        "uuid": "2ea825c4-6086-4ec8-b355-44f7355d4bc0"
                    },
                    {
                        "id": 7,
                        "campaign_id": 3,
                        "name": "Request dynamic content",
                        "description": null,
                        "type": "dwc.decision",
                        "event_type": "decision",
                        "event_order": 1,
                        "properties": {
                            "canvasSettings": {
                                "droppedX": "553",
                                "droppedY": "158"
                            },
                            "name": "",
                            "anchor": "leadsource",
                            "properties": {
                                "dwc_slot_name": "test",
                                "dynamicContent": "1"
                            },
                            "type": "dwc.decision",
                            "eventType": "decision",
                            "anchorEventType": "source",
                            "campaignId": "3",
                            "_token": "04feb2c5a9.SYXSFiT87s-Zw_AIqYLsKEyHuLu3w2dVy9oma5rraFw.GsjjfWq5r6LBgrl-2NO-ay7_7PmOkh8ls7QeIN2xKR0Yt4g7c5KPqfyklw",
                            "buttons": {
                                "save": ""
                            },
                            "triggerDate": null,
                            "dwc_slot_name": "test",
                            "dynamicContent": "1"
                        },
                        "trigger_interval": 0,
                        "trigger_interval_unit": null,
                        "trigger_mode": null,
                        "triggerDate": null,
                        "channel": "dynamicContent",
                        "channel_id": 1,
                        "parent_id": null,
                        "uuid": "21ec254c-68d5-4675-aae0-4d698676a9ce"
                    },
                    {
                        "id": 8,
                        "campaign_id": 3,
                        "name": "Visits a page",
                        "description": null,
                        "type": "page.pagehit",
                        "event_type": "decision",
                        "event_order": 1,
                        "properties": {
                            "canvasSettings": {
                                "droppedX": "73",
                                "droppedY": "158"
                            },
                            "name": "",
                            "anchor": "leadsource",
                            "properties": {
                                "pages": [
                                    "1"
                                ],
                                "url": "",
                                "referer": ""
                            },
                            "type": "page.pagehit",
                            "eventType": "decision",
                            "anchorEventType": "source",
                            "campaignId": "3",
                            "_token": "5f8ffc233728216b01c925d3b1.-S7eUs8NTLUMFt8d0pR5J8yCaeCdnLbL4RAthmMKL8U.qmPvOYFIDdhUV5Zro8UrZK76PaKkzc67mX4VzSRQboSoHIR_mGMt02lxuA",
                            "buttons": {
                                "save": ""
                            },
                            "triggerDate": null,
                            "pages": [
                                1
                            ],
                            "url": null,
                            "referer": null
                        },
                        "trigger_interval": 0,
                        "trigger_interval_unit": null,
                        "trigger_mode": null,
                        "triggerDate": null,
                        "channel": "page",
                        "channel_id": 1,
                        "parent_id": null,
                        "uuid": "2a29709f-0cf7-435e-afab-aff130965789"
                    },
                    {
                        "id": 9,
                        "campaign_id": 3,
                        "name": "test",
                        "description": null,
                        "type": "lead.points",
                        "event_type": "condition",
                        "event_order": 1,
                        "properties": {
                            "canvasSettings": {
                                "droppedX": "10",
                                "droppedY": "263"
                            },
                            "name": "test",
                            "triggerMode": "immediate",
                            "triggerDate": null,
                            "triggerInterval": "1",
                            "triggerIntervalUnit": "d",
                            "triggerHour": "",
                            "triggerRestrictedStartHour": "",
                            "triggerRestrictedStopHour": "",
                            "triggerWindow": "0",
                            "anchor": "leadsource",
                            "properties": {
                                "operator": "=",
                                "score": "1111",
                                "group": "1"
                            },
                            "type": "lead.points",
                            "eventType": "condition",
                            "anchorEventType": "source",
                            "campaignId": "3",
                            "_token": "91d612e76a6bf.bDrd_GtYJSCQAa8oNqzeGhljlPlKZ9gy66WBo3Qer08.P3fslyUdZE3IQOZeR_2MWXsbwLtzNqBCk8u56DNE7g49CIfRPDZERvVmyA",
                            "buttons": {
                                "save": ""
                            },
                            "operator": "=",
                            "score": 1111,
                            "group": 1
                        },
                        "trigger_interval": 1,
                        "trigger_interval_unit": "d",
                        "trigger_mode": "immediate",
                        "triggerDate": null,
                        "channel": null,
                        "channel_id": 0,
                        "parent_id": null,
                        "uuid": "bc70df3e-7d49-4d12-b6ad-992f1e3205a4"
                    },
                    {
                        "id": 6,
                        "campaign_id": 3,
                        "name": "test",
                        "description": null,
                        "type": "email.send",
                        "event_type": "action",
                        "event_order": 2,
                        "properties": {
                            "canvasSettings": {
                                "droppedX": "213",
                                "droppedY": "263"
                            },
                            "name": "test",
                            "triggerMode": "immediate",
                            "triggerDate": null,
                            "triggerInterval": "1",
                            "triggerIntervalUnit": "d",
                            "triggerHour": "",
                            "triggerRestrictedStartHour": "",
                            "triggerRestrictedStopHour": "",
                            "triggerWindow": "0",
                            "anchor": "yes",
                            "properties": {
                                "email": "1",
                                "email_type": "marketing",
                                "priority": "2",
                                "attempts": "3"
                            },
                            "type": "email.send",
                            "eventType": "action",
                            "anchorEventType": "decision",
                            "campaignId": "3",
                            "_token": "fa386d8.ZuglYE5nMxIig26ZT_ooaIBQV5jFhTurjLKYxG0W_dM.NaUUCwAicn96wifvPqt6K-IoA9r81EPb9NygjypMvJI32n9NGQlSdEfkCQ",
                            "buttons": {
                                "save": ""
                            },
                            "email": "1",
                            "email_type": "marketing",
                            "priority": 2,
                            "attempts": 3
                        },
                        "trigger_interval": 1,
                        "trigger_interval_unit": "d",
                        "trigger_mode": "immediate",
                        "triggerDate": null,
                        "channel": "email",
                        "channel_id": 1,
                        "parent_id": 5,
                        "uuid": "63077b6b-800b-4ed1-88ca-3267e3012947"
                    }
                ],
                "asset": [
                    {
                        "id": 2,
                        "is_published": true,
                        "title": "stesetste",
                        "description": null,
                        "alias": "stesetste",
                        "storage_location": "local",
                        "path": "e2ac32c7d50379f5df60cc43d1e7181fa5e94ab9.png",
                        "remote_path": null,
                        "original_file_name": "icon_128x128.png",
                        "lang": "en",
                        "publish_up": null,
                        "publish_down": null,
                        "extension": "png",
                        "mime": "image\/png",
                        "size": 10,
                        "disallow": true,
                        "uuid": "21a5c18a-3c34-4871-92c1-b698eb5ac80f"
                    }
                ],
                "dynamicContent": [
                    {
                        "id": 1,
                        "translation_parent_id": null,
                        "variant_parent_id": null,
                        "is_published": true,
                        "name": "test",
                        "description": null,
                        "publish_up": null,
                        "publish_down": null,
                        "content": null,
                        "utm_tags": {
                            "utmSource": null,
                            "utmMedium": null,
                            "utmCampaign": null,
                            "utmContent": null
                        },
                        "lang": "en",
                        "variant_settings": [],
                        "variant_start_date": null,
                        "filters": [],
                        "is_campaign_based": true,
                        "slot_name": "",
                        "uuid": "107ac8a2-ec01-4ea9-88e8-80d24e2a63bc"
                    }
                ],
                "page": [
                    {
                        "id": 1,
                        "is_published": true,
                        "title": "test page",
                        "alias": "test-page",
                        "template": "blank",
                        "custom_html": "<!DOCTYPE html>\r\n<html>\r\n    <head>\r\n        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\" \/>\r\n                <title>{pagetitle}<\/title>\r\n            <meta name=\"description\" content=\"{pagemetadescription}\" \/>\r\n                                <link rel=\"stylesheet\" href=\"\/plugins\/GrapesJsBuilderBundle\/Assets\/library\/js\/dist\/builder.css?v0dd92de1\" data-source=\"mautic\" \/>\r\n<link rel=\"stylesheet\" href=\"https:\/\/fonts.googleapis.com\/css?family=Ubuntu\" data-source=\"mautic\" \/>\r\n<link rel=\"stylesheet\" href=\"https:\/\/fonts.googleapis.com\/css?family=Source+Sans+Pro\" data-source=\"mautic\" \/>\r\n<link rel=\"stylesheet\" href=\"https:\/\/fonts.googleapis.com\/css?family=Roboto\" data-source=\"mautic\" \/>\r\n<link rel=\"stylesheet\" href=\"https:\/\/fonts.googleapis.com\/css?family=Open+Sans\" data-source=\"mautic\" \/>\r\n<link rel=\"stylesheet\" href=\"https:\/\/fonts.googleapis.com\/css?family=Montserrat\" data-source=\"mautic\" \/>\r\n<link rel=\"stylesheet\" href=\"https:\/\/fonts.googleapis.com\/css?family=Lato\" data-source=\"mautic\" \/>\r\n<link rel=\"stylesheet\" href=\"https:\/\/fonts.googleapis.com\/css?family=Droid+Serif\" data-source=\"mautic\" \/>\r\n<link rel=\"stylesheet\" href=\"https:\/\/fonts.googleapis.com\/css?family=Bitter\" data-source=\"mautic\" \/>\r\n\r\n<script src=\"\/plugins\/GrapesJsBuilderBundle\/Assets\/library\/js\/dist\/builder.js?v0dd92de1\" data-source=\"mautic\"><\/script>\r\n    <\/head>\r\n    <body>\r\n        \r\n        <div data-section-wrapper=\"1\">\r\n    <center>\r\n        <table data-section=\"1\" style=\"width: 600;\" width=\"600\" cellpadding=\"0\" cellspacing=\"0\">\r\n            <tbody>\r\n                <tr>\r\n                    <td>\r\n                        <div data-slot-container=\"1\" style=\"min-height: 30px\">\r\n                            <div data-slot=\"text\">\r\n                                <br \/>\r\n                                <h2>Hello there!<\/h2>\r\n                                <br \/>\r\n                                We haven't heard from you for a while...\r\n                                <br \/>\r\n                                <br \/>\r\n                                <br \/>\r\n                            <\/div>\r\n                        <\/div>\r\n                    <\/td>\r\n                <\/tr>\r\n            <\/tbody>\r\n        <\/table>\r\n    <\/center>\r\n<\/div>\r\n        \r\n    <\/body>\r\n<\/html>",
                        "content": [],
                        "publish_up": null,
                        "publish_down": null,
                        "hits": 0,
                        "unique_hits": 0,
                        "variant_hits": 0,
                        "revision": 1,
                        "meta_description": null,
                        "head_script": null,
                        "footer_script": null,
                        "redirect_type": null,
                        "redirect_url": null,
                        "is_preference_center": false,
                        "no_index": false,
                        "lang": "en",
                        "variant_settings": [],
                        "uuid": "401fbafc-707e-44d8-87c2-3f823f17546b"
                    }
                ],
                "pointGroup": [
                    {
                        "id": 1,
                        "name": "test",
                        "description": null,
                        "is_published": true,
                        "uuid": "895c3582-99d7-4174-bb2b-e33a38324882"
                    }
                ],
                "email": [
                    {
                        "id": 1,
                        "translation_parent_id": null,
                        "variant_parent_id": null,
                        "unsubscribeform_id": null,
                        "preference_center_id": null,
                        "is_published": true,
                        "name": "test email 1",
                        "description": null,
                        "subject": "test",
                        "preheader_text": null,
                        "from_name": null,
                        "use_owner_as_mailer": false,
                        "template": "mautic_code_mode",
                        "content": [],
                        "utm_tags": {
                            "utmSource": null,
                            "utmMedium": null,
                            "utmCampaign": null,
                            "utmContent": null
                        },
                        "plain_text": null,
                        "custom_html": "<!doctype html>\r\n<html lang=\"und\" dir=\"auto\" xmlns=\"http:\/\/www.w3.org\/1999\/xhtml\" xmlns:v=\"urn:schemas-microsoft-com:vml\" xmlns:o=\"urn:schemas-microsoft-com:office:office\">\r\n\r\n<head>\r\n  <title><\/title>\r\n  <!--[if !mso]><!-->\r\n  <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">\r\n  <!--<![endif]-->\r\n  <meta http-equiv=\"Content-Type\" content=\"text\/html; charset=UTF-8\">\r\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\r\n  <style type=\"text\/css\">\r\n    #outlook a {\r\n      padding: 0;\r\n    }\r\n\r\n    body {\r\n      margin: 0;\r\n      padding: 0;\r\n      -webkit-text-size-adjust: 100%;\r\n      -ms-text-size-adjust: 100%;\r\n    }\r\n\r\n    table,\r\n    td {\r\n      border-collapse: collapse;\r\n      mso-table-lspace: 0pt;\r\n      mso-table-rspace: 0pt;\r\n    }\r\n\r\n    img {\r\n      border: 0;\r\n      height: auto;\r\n      line-height: 100%;\r\n      outline: none;\r\n      text-decoration: none;\r\n      -ms-interpolation-mode: bicubic;\r\n    }\r\n\r\n    p {\r\n      display: block;\r\n      margin: 13px 0;\r\n    }\r\n  <\/style>\r\n  <!--[if mso]>\r\n    <noscript>\r\n    <xml>\r\n    <o:OfficeDocumentSettings>\r\n      <o:AllowPNG\/>\r\n      <o:PixelsPerInch>96<\/o:PixelsPerInch>\r\n    <\/o:OfficeDocumentSettings>\r\n    <\/xml>\r\n    <\/noscript>\r\n    <![endif]-->\r\n  <!--[if lte mso 11]>\r\n    <style type=\"text\/css\">\r\n      .mj-outlook-group-fix { width:100% !important; }\r\n    <\/style>\r\n    <![endif]-->\r\n  <!--[if !mso]><!-->\r\n  <link href=\"https:\/\/fonts.googleapis.com\/css?family=Open+Sans:300,400,500,700\" rel=\"stylesheet\" type=\"text\/css\">\r\n  <link href=\"https:\/\/fonts.googleapis.com\/css?family=Ubuntu:300,400,500,700\" rel=\"stylesheet\" type=\"text\/css\">\r\n  <style type=\"text\/css\">\r\n    @import url(https:\/\/fonts.googleapis.com\/css?family=Open+Sans:300,400,500,700);\r\n    @import url(https:\/\/fonts.googleapis.com\/css?family=Ubuntu:300,400,500,700);\r\n  <\/style>\r\n  <!--<![endif]-->\r\n  <style type=\"text\/css\">\r\n    @media only screen and (min-width:480px) {\r\n      .mj-column-px-550 {\r\n        width: 550px !important;\r\n        max-width: 550px;\r\n      }\r\n    }\r\n  <\/style>\r\n  <style media=\"screen and (min-width:480px)\">\r\n    .moz-text-html .mj-column-px-550 {\r\n      width: 550px !important;\r\n      max-width: 550px;\r\n    }\r\n  <\/style>\r\n  <!-- CSS-STYLE -->\r\n<\/head>\r\n\r\n<body style=\"word-spacing:normal;\">\r\n  <div style lang=\"und\" dir=\"auto\">\r\n    <!--[if mso | IE]><table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"\" role=\"presentation\" style=\"width:600px;\" width=\"600\" bgcolor=\"#ffffff\" ><tr><td style=\"line-height:0px;font-size:0px;mso-line-height-rule:exactly;\"><![endif]-->\r\n    <div style=\"background:#ffffff;background-color:#ffffff;margin:0px auto;max-width:600px;\">\r\n      <table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" role=\"presentation\" style=\"background:#ffffff;background-color:#ffffff;width:100%;\">\r\n        <tbody>\r\n          <tr>\r\n            <td style=\"direction:ltr;font-size:0px;padding:20px 0;text-align:center;\">\r\n              <!--[if mso | IE]><table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\"><tr><td class=\"\" style=\"vertical-align:top;width:550px;\" ><![endif]-->\r\n              <div class=\"mj-column-px-550 mj-outlook-group-fix\" style=\"font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;\">\r\n                <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" role=\"presentation\" style=\"vertical-align:top;\" width=\"100%\">\r\n                  <tbody>\r\n                    <tr>\r\n                      <td style=\"font-size:0px;word-break:break-word;\">\r\n                        <div style=\"height:20px;line-height:20px;\">&#8202;<\/div>\r\n                      <\/td>\r\n                    <\/tr>\r\n                    <tr>\r\n                      <td style=\"font-size:0px;word-break:break-word;\">\r\n                        <div style=\"height:20px;line-height:20px;\">&#8202;<\/div>\r\n                      <\/td>\r\n                    <\/tr>\r\n                    <tr>\r\n                      <td align=\"left\" style=\"font-size:0px;padding:10px 25px;word-break:break-word;\">\r\n                        <div style=\"font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:28px;font-weight:700;line-height:1;text-align:left;color:#000000;\">\r\n                          <p style=\"padding: 0; line-height: 1.4em; font-family: 'Open Sans', Helvetica, Arial, sans-serif; margin: 0;\">Hello World! <\/p>\r\n                        <\/div>\r\n                      <\/td>\r\n                    <\/tr>\r\n                    <tr>\r\n                      <td align=\"left\" style=\"font-size:0px;padding:10px 25px;word-break:break-word;\">\r\n                        <div style=\"font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:14px;line-height:1;text-align:left;color:#000000;\">\r\n                          <p style=\"padding: 0; line-height: 1.4em; font-family: 'Open Sans', Helvetica, Arial, sans-serif; margin: 0;\">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Aliquid officia consequatur placeat reprehenderit excepturi, tempore, id quos quaerat ab fuga. <\/p>\r\n                          <p style=\"padding: 0; line-height: 1.4em; font-family: 'Open Sans', Helvetica, Arial, sans-serif; margin: 0;\">\r\n                            <br data-cke-filler=\"true\">\r\n                          <\/p>\r\n                          <p style=\"padding: 0; line-height: 1.4em; font-family: 'Open Sans', Helvetica, Arial, sans-serif; margin: 0;\">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Inventore, voluptate. <\/p>\r\n                          <p style=\"padding: 0; line-height: 1.4em; font-family: 'Open Sans', Helvetica, Arial, sans-serif; margin: 0;\">\r\n                            <br data-cke-filler=\"true\">\r\n                          <\/p>\r\n                          <p style=\"padding: 0; line-height: 1.4em; font-family: 'Open Sans', Helvetica, Arial, sans-serif; margin: 0;\">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Dignissimos alias rerum nemo ducimus modi perspiciatis. <\/p>\r\n                        <\/div>\r\n                      <\/td>\r\n                    <\/tr>\r\n                    <tr>\r\n                      <td style=\"font-size:0px;word-break:break-word;\">\r\n                        <div style=\"height:20px;line-height:20px;\">&#8202;<\/div>\r\n                      <\/td>\r\n                    <\/tr>\r\n                    <tr>\r\n                      <td align=\"left\" style=\"font-size:0px;padding:10px 25px;word-break:break-word;\">\r\n                        <div style=\"font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:11px;line-height:1;text-align:left;color:#6d6d6d;\">\r\n                          <p style=\"padding: 0; line-height: 1.4em; font-family: 'Open Sans', Helvetica, Arial, sans-serif; margin: 0;\">{unsubscribe_text} | {webview_text}<\/p>\r\n                        <\/div>\r\n                      <\/td>\r\n                    <\/tr>\r\n                    <tr>\r\n                      <td style=\"font-size:0px;word-break:break-word;\">\r\n                        <div style=\"height:20px;line-height:20px;\">&#8202;<\/div>\r\n                      <\/td>\r\n                    <\/tr>\r\n                    <tr>\r\n                      <td style=\"font-size:0px;word-break:break-word;\">\r\n                        <div style=\"height:20px;line-height:20px;\">&#8202;<\/div>\r\n                      <\/td>\r\n                    <\/tr>\r\n                  <\/tbody>\r\n                <\/table>\r\n              <\/div>\r\n              <!--[if mso | IE]><\/td><\/tr><\/table><![endif]-->\r\n            <\/td>\r\n          <\/tr>\r\n        <\/tbody>\r\n      <\/table>\r\n    <\/div>\r\n    <!--[if mso | IE]><\/td><\/tr><\/table><![endif]-->\r\n  <\/div>\r\n<\/body>\r\n\r\n<\/html>",
                        "email_type": "template",
                        "publish_up": null,
                        "publish_down": null,
                        "revision": 1,
                        "lang": "en",
                        "variant_settings": [],
                        "variant_start_date": null,
                        "dynamic_content": [
                            {
                                "tokenName": "Dynamic Content 1",
                                "content": "Default Dynamic Content",
                                "filters": [
                                    {
                                        "content": null,
                                        "filters": []
                                    }
                                ]
                            }
                        ],
                        "headers": [],
                        "public_preview": false,
                        "uuid": "e9315582-087e-42f1-a9c5-3af468fd522c"
                    }
                ],
                "lists": [
                    {
                        "id": 1,
                        "name": "Test Seg",
                        "is_published": true,
                        "description": null,
                        "alias": "test-seg",
                        "public_name": "Test Seg",
                        "filters": [],
                        "is_global": true,
                        "is_preference_center": false,
                        "uuid": "d697157e-9ae3-4600-aa2e-4a2a5a6e36e0"
                    }
                ],
                "forms": [
                    {
                        "id": 1,
                        "name": "test form",
                        "is_published": true,
                        "description": null,
                        "alias": "test_form",
                        "lang": null,
                        "cached_html": "\n<style type=\"text\/css\" scoped>\n    .mauticform_wrapper { max-width: 600px; margin: 10px auto; }\n    .mauticform-innerform {}\n    .mauticform-post-success {}\n    .mauticform-name { font-weight: bold; font-size: 1.5em; margin-bottom: 3px; }\n    .mauticform-description { margin-top: 2px; margin-bottom: 10px; }\n    .mauticform-error { margin-bottom: 10px; color: red; }\n    .mauticform-message { margin-bottom: 10px; color: green; }\n    .mauticform-row { display: block; margin-bottom: 20px; }\n    .mauticform-label { font-size: 1.1em; display: block; font-weight: bold; margin-bottom: 5px; }\n    .mauticform-row.mauticform-required .mauticform-label:after { color: #e32; content: \" *\"; display: inline; }\n    .mauticform-helpmessage { display: block; font-size: 0.9em; margin-bottom: 3px; }\n    .mauticform-errormsg { display: block; color: red; margin-top: 2px; }\n    .mauticform-selectbox, .mauticform-input, .mauticform-textarea { width: 100%; padding: 0.5em 0.5em; border: 1px solid #CCC; background: #fff; box-shadow: 0px 0px 0px #fff inset; border-radius: 4px; box-sizing: border-box; }\n    .mauticform-checkboxgrp-row {}\n    .mauticform-checkboxgrp-label { font-weight: normal; }\n    .mauticform-checkboxgrp-checkbox {}\n    .mauticform-radiogrp-row {}\n    .mauticform-radiogrp-label { font-weight: normal; }\n    .mauticform-radiogrp-radio {}\n    .mauticform-button-wrapper .mauticform-button.btn-ghost, .mauticform-pagebreak-wrapper .mauticform-pagebreak.btn-ghost { color: #5d6c7c;background-color: #ffffff;border-color: #dddddd;}\n    .mauticform-button-wrapper .mauticform-button, .mauticform-pagebreak-wrapper .mauticform-pagebreak { display: inline-block;margin-bottom: 0;font-weight: 600;text-align: center;vertical-align: middle;cursor: pointer;background-image: none;border: 1px solid transparent;white-space: nowrap;padding: 6px 12px;font-size: 13px;line-height: 1.3856;border-radius: 3px;-webkit-user-select: none;-moz-user-select: none;-ms-user-select: none;user-select: none;}\n    .mauticform-button-wrapper .mauticform-button.btn-ghost[disabled], .mauticform-pagebreak-wrapper .mauticform-pagebreak.btn-ghost[disabled] { background-color: #ffffff; border-color: #dddddd; opacity: 0.75; cursor: not-allowed; }\n    .mauticform-pagebreak-wrapper .mauticform-button-wrapper {  display: inline; }\n\n    \/* Make fields display inline when using width classes *\/\n    .mauticform-page-wrapper {\n        display: flex;\n        flex-wrap: wrap;\n        width: 100%;\n        margin: 0 -10px;\n    }\n\n    \/* Ensure field containers respect width classes *\/\n    .mauticform-row {\n        box-sizing: border-box;\n        padding: 0 10px;\n        margin-bottom: 15px;\n    }\n\n    \/* Responsive adjustment for mobile *\/\n    @media (max-width: 767px) {\n        .mauticform-three-quarters-width,\n        .mauticform-two-thirds-width,\n        .mauticform-half-width,\n        .mauticform-one-third-width,\n        .mauticform-one-quarter-width {\n            width: 100%;\n        }\n    }\n\n    \/**\n    * @see https:\/\/github.com\/TarekRaafat\/autoComplete.js\/blob\/master\/dist\/css\/autoComplete.02.css.\n    *\/\n    .autoComplete_wrapper {position: relative;}\n    .autoComplete_wrapper > input::placeholder {transition: all 0.3s ease;}\n    .autoComplete_wrapper > ul {position: absolute;max-height: 226px;overflow-y: scroll;top: 100%;left: 0;right: 0;padding: 0;margin: 0.5rem 0 0 0;border-radius: 4px;background-color: #fff;border: 1px solid rgba(33, 33, 33, 0.1);z-index: 1000;outline: none;}\n    .autoComplete_wrapper > ul > li {padding: 10px 20px;list-style: none;text-align: left;font-size: 16px;color: #212121;transition: all 0.1s ease-in-out;border-radius: 3px;background-color: rgba(255, 255, 255, 1);white-space: nowrap;overflow: hidden;text-overflow: ellipsis;transition: all 0.2s ease;}\n    .autoComplete_wrapper > ul > li > span {float: right;}\n    .autoComplete_wrapper > ul > li::selection {color: rgba(#ffffff, 0);background-color: rgba(#ffffff, 0);}\n    .autoComplete_wrapper > ul > li:hover {cursor: pointer;background-color: rgba(123, 123, 123, 0.1);}\n    .autoComplete_wrapper > ul > li mark {background-color: transparent;font-weight: bold;}\n    .autoComplete_wrapper > ul > li mark::selection {background-color: rgba(#ffffff, 0);}\n    .autoComplete_wrapper > ul > li[aria-selected=\"true\"] {background-color: rgba(123, 123, 123, 0.1);}\n    @media only screen and (max-width: 600px) {\n      .autoComplete_wrapper > input {width: 18rem;}\n    }\n<\/style>\n\n<style type=\"text\/css\" scoped>\n    .mauticform-field-hidden { display:none }\n<\/style>\n<div id=\"mauticform_wrapper_testform\" class=\"mauticform_wrapper\">\n    <form autocomplete=\"false\" role=\"form\" method=\"post\" action=\"https:\/\/127.0.0.1:32772\/form\/submit?formId=1\" id=\"mauticform_testform\" data-mautic-form=\"testform\" enctype=\"multipart\/form-data\" ><div class=\"mauticform-error\" id=\"mauticform_testform_error\"><\/div>\n            <div class=\"mauticform-message\" id=\"mauticform_testform_message\"><\/div><div class=\"mauticform-innerform\">\n            <div class=\"mauticform-page-wrapper mauticform-page-1\" data-mautic-form-page=\"1\" >\n                  \n      \n  \n    \n    \n\n<div id=\"mauticform_testform_submit\"class=\"mauticform-row mauticform-button-wrapper mauticform-field-1\"style=\"width: 100%\">\n  <button class=\"btn btn-ghost mauticform-button\"name=\"mauticform[submit]\"value=\"1\"id=\"mauticform_input_testform_submit\"type=\"submit\">Submit<\/button>\n<\/div>\n                  <\/div><\/div><input type=\"hidden\" name=\"mauticform[formId]\" id=\"mauticform_testform_id\" value=\"1\"\/>\n        <input type=\"hidden\" name=\"mauticform[return]\" id=\"mauticform_testform_return\" value=\"\"\/>\n        <input type=\"hidden\" name=\"mauticform[formName]\" id=\"mauticform_testform_name\" value=\"testform\"\/>\n        \n    <\/form>\n<\/div>\n",
                        "post_action": "return",
                        "template": null,
                        "form_type": "campaign",
                        "render_style": true,
                        "post_action_property": null,
                        "form_attr": null,
                        "uuid": "c98d1a1b-1a12-4931-bb4d-3583394cd573"
                    }
                ],
                "form_fields": [
                    {
                        "id": 1,
                        "uuid": "74f101ad-94b6-4013-bd7d-9dbba719637a",
                        "label": "Submit",
                        "show_label": true,
                        "alias": "submit",
                        "type": "button",
                        "is_custom": false,
                        "custom_parameters": [],
                        "default_value": null,
                        "is_required": false,
                        "validation_message": null,
                        "help_message": null,
                        "field_order": 1,
                        "properties": [],
                        "validation": [],
                        "parent_id": null,
                        "conditions": [],
                        "label_attr": null,
                        "input_attr": "class=\"btn btn-ghost\"",
                        "container_attr": null,
                        "save_result": true,
                        "is_auto_fill": false,
                        "show_when_value_exists": null,
                        "show_after_x_submissions": null,
                        "mapped_object": null,
                        "mapped_field": null,
                        "form": 1
                    }
                ],
                "dependencies": [
                    {
                        "campaign_event": [
                            {
                                "campaign": 3,
                                "campaign_event": 5,
                                "asset": 2
                            },
                            {
                                "campaign": 3,
                                "campaign_event": 7,
                                "dynamicContent": 1
                            },
                            {
                                "campaign": 3,
                                "campaign_event": 8,
                                "page": 1
                            },
                            {
                                "campaign": 3,
                                "campaign_event": 9,
                                "pointGroup": 1
                            },
                            {
                                "campaign": 3,
                                "campaign_event": 6,
                                "email": 1
                            }
                        ],
                        "lists": [
                            {
                                "campaign": 3,
                                "lists": 1
                            }
                        ],
                        "forms": [
                            {
                                "forms": 1,
                                "form_fields": 1
                            },
                            {
                                "campaign": 3,
                                "forms": 1
                            }
                        ]
                    }
                ]
            }
        ]
        JSON;

        return json_decode($data, true);
    }
}
