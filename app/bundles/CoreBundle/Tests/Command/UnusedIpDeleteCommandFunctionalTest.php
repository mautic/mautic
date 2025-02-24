<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Command;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Entity\IpAddressRepository;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class UnusedIpDeleteCommandFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @throws \Exception
     */
    public function testUnusedIpDeleteCommand(): void
    {
        // Emulate unused IP address.
        /** @var IpAddressRepository $ipAddressRepo */
        $ipAddressRepo = $this->em->getRepository(IpAddress::class);
        $ipAddressRepo->saveEntity(new IpAddress('127.0.0.1'));
        $count = $ipAddressRepo->count(['ipAddress' => '127.0.0.1']);
        self::assertSame(1, $count);

        // Delete unused IP address.
        $this->testSymfonyCommand('mautic:unusedip:delete');

        $count = $ipAddressRepo->count(['ipAddress' => '127.0.0.1']);
        self::assertSame(0, $count);
    }
}
