<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Command;

use Mautic\CoreBundle\Command\AnonymizeIpCommand;
use Mautic\CoreBundle\Entity\IpAddress;
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
        $response = $this->testSymfonyCommand(AnonymizeIpCommand::COMMAND_NAME);
        Assert::assertStringContainsString('Anonymization could not be done because anonymize Ip feature is disabled for this instance.', $response->getDisplay());
        $ipAddressList = $this->em->getRepository(IpAddress::class)->findBy(['ipAddress' => '*.*.*.*']);
        Assert::assertCount(0, $ipAddressList);
    }

    public function testAnonymizeIpCommandWithFeatureEnable(): void
    {
        $this->createIpAddress();

        $this->testSymfonyCommand(AnonymizeIpCommand::COMMAND_NAME);
        $this->em->clear();
        $ipAddressList = $this->em->getRepository(IpAddress::class)->findBy(['ipAddress' => '*.*.*.*']);
        Assert::assertCount(1, $ipAddressList);
        Assert::assertNull($ipAddressList[0]->getIpDetails());
    }

    private function createIpAddress(): IpAddress
    {
        $ipAddress = new IpAddress();
        $ipAddress->setIpAddress('192.168.8.9');
        $ipAddress->setIpDetails(['city' => 'Boston', 'region' => 'MA', 'country' => 'United States', 'zipcode' => '02113']);
        $this->em->persist($ipAddress);
        $this->em->flush();

        return $ipAddress;
    }
}
