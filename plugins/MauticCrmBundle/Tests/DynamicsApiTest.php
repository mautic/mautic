<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Tests;

use Mautic\PluginBundle\Tests\Integration\AbstractIntegrationTestCase;
use MauticPlugin\MauticCrmBundle\Api\DynamicsApi;
use MauticPlugin\MauticCrmBundle\Integration\DynamicsIntegration;

final class DynamicsApiTest extends AbstractIntegrationTestCase
{
    private DynamicsIntegration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->integration = new DynamicsIntegration(
            $this->dispatcher,
            $this->cache,
            $this->em,
            $this->request,
            $this->router,
            $this->translator,
            $this->logger,
            $this->encryptionHelper,
            $this->leadModel,
            $this->companyModel,
            $this->pathsHelper,
            $this->notificationModel,
            $this->fieldModel,
            $this->integrationEntityModel,
            $this->doNotContact,
            $this->fieldsWithUniqueIdentifier
        );

        /** @phpstan-ignore new.resultUnused */
        new DynamicsApi($this->integration);
    }

    public function testIntegration(): void
    {
        $this->assertSame('Dynamics', $this->integration->getName());
    }
}
