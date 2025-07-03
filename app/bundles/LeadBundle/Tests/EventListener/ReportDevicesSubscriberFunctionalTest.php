<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\ReportBundle\Entity\Report;
use Symfony\Component\HttpFoundation\Request;

class ReportDevicesSubscriberFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $contactData = [
        [
            'email'   => 'test1@example.com',
            'devices' => [
                [
                    'type'       => 'desktop',
                    'dateAdded'  => '2020-02-07 20:29:02',
                    'clientInfo' => [
                        'type'           => 'browser',
                        'name'           => 'Firefox',
                        'short_name'     => 'FF',
                        'version'        => '99.0',
                        'engine'         => 'Gecko',
                        'engine_version' => '99.0',
                    ],
                    'deviceOs' => [
                        'name'       => 'Windows',
                        'short_name' => 'WIN',
                        'version'    => '10',
                        'platform'   => 'x64',
                    ],
                    'trackingId' => '9743a66f914cc249efca164485a19c5c',
                ],
            ],
        ],
        [
            'email'   => 'test2@example.com',
            'devices' => [
                [
                    'type'       => 'desktop',
                    'dateAdded'  => '2020-02-26 22:29:02',
                    'clientInfo' => [
                        'type'           => 'browser',
                        'name'           => 'Firefox',
                        'short_name'     => 'FF',
                        'version'        => '105.0',
                        'engine'         => 'Gecko',
                        'engine_version' => '105.0',
                        'family'         => 'Firefox',
                    ],
                    'deviceOs' => [
                        'name'       => 'Ubuntu',
                        'short_name' => 'UBT',
                        'platform'   => 'x64',
                    ],
                    'trackingId' => 'of7qikto8j10qfpbgu807bt',
                ],
                [
                    'type'        => 'smartphone',
                    'deviceModel' => 'Salaxy S0',
                    'deviceBrand' => 'NoPhone',
                    'dateAdded'   => '2022-09-21 07:36:48',
                    'clientInfo'  => [
                        'type'           => 'browser',
                        'name'           => 'Chrome Mobile',
                        'short_name'     => 'CM',
                        'version'        => '87.0',
                        'engine'         => 'Blink',
                        'engine_version' => '',
                        'family'         => 'Chrome',
                    ],
                    'deviceOs' => [
                        'name'       => 'Android',
                        'short_name' => 'AND',
                        'version'    => '8.0',
                    ],
                    'trackingId' => 'f2mz3rhafvas6k6gyh54lur',
                ],
            ],
        ],
    ];

    public function testOnReportGenerate(): void
    {
        $countDevices = 0;
        $leadIds      = [];

        foreach ($this->contactData as $el) {
            $contact   = $this->createContact($el['email']);
            $leadIds[] = $contact->getId();

            foreach ($el['devices'] as $device) {
                $this->createDevice(
                    $contact,
                    $device['type'],
                    $device['dateAdded'],
                    $device['deviceOs'],
                    $device['clientInfo'],
                    $device['trackingId'],
                    $device['deviceModel'] ?? null,
                    $device['deviceBrand'] ?? null,
                );
                ++$countDevices;
            }
        }

        $report = $this->createReport();

        $this->client->request(Request::METHOD_GET, "/api/reports/{$report->getId()}");
        $clientResponse = $this->client->getResponse();
        $result         = json_decode($clientResponse->getContent(), true);
        $this->assertEquals($countDevices, $result['totalResults']);
        $this->assertEquals([
            [
                'id'                 => $leadIds[0],
                'device'             => 'desktop',
                'device_os_name'     => 'Windows',
                'device_os_version'  => '10',
                'device_os_platform' => 'x64',
                'client_info'        => 'Firefox',
                'device_brand'       => '',
                'device_model'       => '',
                'date_added2'        => '2020-02-07 20:29:02',
            ],
            [
                'id'                 => $leadIds[1],
                'device'             => 'desktop',
                'device_os_name'     => 'Ubuntu',
                'device_os_version'  => '',
                'device_os_platform' => 'x64',
                'client_info'        => 'Firefox',
                'device_brand'       => '',
                'device_model'       => '',
                'date_added2'        => '2020-02-26 22:29:02',
            ],
            [
                'id'                 => $leadIds[1],
                'device'             => 'smartphone',
                'device_os_name'     => 'Android',
                'device_os_version'  => '8.0',
                'device_os_platform' => '',
                'client_info'        => 'Chrome Mobile',
                'device_brand'       => 'Salaxy S0',
                'device_model'       => 'NoPhone',
                'date_added2'        => '2022-09-21 07:36:48',
            ],
        ], $result['data']);
    }

    private function createContact(string $email): Lead
    {
        $contact = new Lead();
        $contact->setEmail($email);
        $this->em->persist($contact);
        $this->em->flush();

        return $contact;
    }

    /**
     * @param array<string, string> $deviceOs
     * @param array<string, string> $clientInfo
     */
    private function createDevice(
        Lead $contact,
        string $deviceType,
        string $date,
        array $deviceOs,
        array $clientInfo,
        string $trackingId,
        ?string $deviceBrand,
        ?string $deviceModel,
    ): LeadDevice {
        $device = new LeadDevice();
        $device->setDateAdded(new \DateTime($date));
        $device->setClientInfo($clientInfo);
        $device->setDevice($deviceType);
        $device->setDeviceOs($deviceOs);
        $device->setTrackingId($trackingId);

        if (isset($deviceModel)) {
            $device->setDeviceModel($deviceModel);
        }
        if (isset($deviceBrand)) {
            $device->setDeviceBrand($deviceBrand);
        }

        $device->setLead($contact);
        $this->em->persist($device);
        $this->em->flush();

        return $device;
    }

    private function createReport(): Report
    {
        $report = new Report();
        $report->setName('Devices');
        $report->setSource('contact.devices');
        $report->setColumns([
            'l.id',
            'dev.date_added',
            'dev.device',
            'dev.device_os_name',
            'dev.device_os_version',
            'dev.device_os_platform',
            'dev.client_info',
            'dev.device_brand',
            'dev.device_model',
        ]);
        $this->em->persist($report);
        $this->em->flush();

        return $report;
    }

    protected function setUp(): void
    {
        parent::setUp();
    }
}
