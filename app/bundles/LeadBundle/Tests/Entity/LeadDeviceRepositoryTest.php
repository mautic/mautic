<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Entity\LeadDeviceRepository;

class LeadDeviceRepositoryTest extends MauticMysqlTestCase
{
    public function testFindExistingDevice(): void
    {
        $lead = new Lead();
        $lead->setEmail('test@example.com');

        $this->em->persist($lead);

        $initialDevice = new LeadDevice();
        $initialDevice->setDateAdded(new \DateTime());
        $initialDevice->setLead($lead);
        $initialDevice->setClientInfo('client info');
        $initialDevice->setDevice('device');
        $initialDevice->setDeviceBrand('device brand');
        $initialDevice->setDeviceModel('device model');
        $initialDevice->setDeviceOs([
            'name'       => 'GNU/Linux',
            'short_name' => 'LIN',
        ]);

        $this->em->persist($initialDevice);
        $this->em->flush();

        $newDevice = new LeadDevice();
        $newDevice->setDateAdded(new \DateTime());
        $newDevice->setLead($lead);
        $newDevice->setClientInfo('client info');
        $newDevice->setDevice('device');
        $newDevice->setDeviceBrand('device brand');
        $newDevice->setDeviceModel('device model');
        $newDevice->setDeviceOs([
            'name'       => 'Windows',
            'short_name' => 'WIN',
        ]);

        /** @var LeadDeviceRepository $leadDeviceRepository */
        $leadDeviceRepository = $this->em->getRepository(LeadDevice::class);
        $existingDevice       = $leadDeviceRepository->findExistingDevice($newDevice);

        // Using assertTrue instead of assertNull to reduce test output
        $this->assertTrue(
            null === $existingDevice,
            'The existing device should be null because the device has a different OS.'
        );
    }
}
