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
use Mautic\CoreBundle\Helper\ExitCode;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;

class AnonymizeIpCommandTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['anonymize_ip'] = 'testAnonymizeIpCommandWithFeatureEnable' === $this->getName();
        parent::setUp();
    }

    public function testAnonymizeIpCommandWithFeatureEnableDisable(): void
    {
        $this->createIpAddress();
        $response = $this->runCommand(AnonymizeIpCommand::COMMAND_NAME, [], null, ExitCode::FAILURE);
        Assert::assertStringContainsString('Anonymization could not be done because anonymize Ip feature is disabled for this instance.', $response);
        $ipAddressList = $this->em->getRepository(IpAddress::class)->findAll();
        Assert::assertCount(1, $ipAddressList);
    }

    public function testAnonymizeIpCommandWithFeatureEnable(): void
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
