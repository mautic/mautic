<?php

namespace MauticPlugin\MauticCrmBundle\Tests;

use Mautic\PluginBundle\Tests\Integration\AbstractIntegrationTestCase;
use MauticPlugin\MauticCrmBundle\Integration\DynamicsIntegration;

class DynamicsIntegrationTest extends AbstractIntegrationTestCase
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
    }

    public function testIntegration(): void
    {
        $this->assertSame('Dynamics', $this->integration->getName());
    }
}
