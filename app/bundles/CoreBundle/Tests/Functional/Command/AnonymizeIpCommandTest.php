<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Functional\Command;

use Mautic\CoreBundle\Command\AnonymizeIpCommand;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;

class AnonymizeIpCommandTest extends MauticMysqlTestCase
{
    public function testAnonymizeIpCommand(): void
    {
        $this->createIpAddress();

        $this->runCommand(AnonymizeIpCommand::COMMAND_NAME);

        $ipAddressList = $this->em->getRepository(IpAddress::class)->findAll();
        Assert::assertCount(0, $ipAddressList);
    }

    private function createIpAddress(): IpAddress
    {
        $ipAddress = new IpAddress();
        $ipAddress->setIpAddress('192.168.8.9');
        $this->em->persist($ipAddress);
        $this->em->flush();

        return $ipAddress;
    }
}
