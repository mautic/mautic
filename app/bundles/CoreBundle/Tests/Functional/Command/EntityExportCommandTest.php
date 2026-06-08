<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Command;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Command\EntityExportCommand;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;

final class EntityExportCommandTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestCampaign();
    }

    public function createTestCampaign(): void
    {
        $campaign = new Campaign();
        $campaign->setName('Test Campaign');
        $campaign->setDescription('A campaign for testing purposes.');
        $this->em->persist($campaign);
        $this->em->flush();
    }

    public function testExecuteFailsWithoutEntityOrId(): void
    {
        $response = $this->testSymfonyCommand(EntityExportCommand::COMMAND_NAME, [
            '--entity' => '',
            '--id'     => '',
        ]);

        Assert::assertStringContainsString('You must specify the entity and at least one valid entity ID.', $response->getDisplay());
        Assert::assertSame(1, $response->getStatusCode());
    }

    public function testExecuteDispatchesEvent(): void
    {
        $entityName = 'campaign';
        $entityId   = $this->getTestCampaignId();

        $response = $this->testSymfonyCommand(EntityExportCommand::COMMAND_NAME, [
            '--entity'    => $entityName,
            '--id'        => (string) $entityId,
            '--json-only' => true,
        ]);

        Assert::assertStringContainsString('"id": '.$entityId, $response->getDisplay());
        Assert::assertSame(0, $response->getStatusCode());
    }

    public function testZipFileOptionCreatesZip(): void
    {
        $entityName = 'campaign';
        $entityId   = $this->getTestCampaignId();

        $response = $this->testSymfonyCommand(EntityExportCommand::COMMAND_NAME, [
            '--entity'   => $entityName,
            '--id'       => (string) $entityId,
            '--zip-file' => true,
        ]);

        Assert::assertStringContainsString('.zip', $response->getDisplay());
        Assert::assertSame(0, $response->getStatusCode());
    }

    private function getTestCampaignId(): int
    {
        $campaign = $this->em->getRepository(Campaign::class)->findOneBy(['name' => 'Test Campaign']);

        return $campaign->getId();
    }
}
