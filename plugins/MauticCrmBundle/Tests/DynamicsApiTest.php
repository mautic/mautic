<?php

namespace MauticPlugin\MauticCrmBundle\Tests;

use Mautic\PluginBundle\Tests\Integration\AbstractIntegrationTestCase;
use MauticPlugin\MauticCrmBundle\Api\DynamicsApi;
use MauticPlugin\MauticCrmBundle\Integration\DynamicsIntegration;

class DynamicsApiTest extends AbstractIntegrationTestCase
{
    private \MauticPlugin\MauticCrmBundle\Api\DynamicsApi $api;

    private \MauticPlugin\MauticCrmBundle\Integration\DynamicsIntegration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->integration = new DynamicsIntegration(
            $this->dispatcher,
            $this->cache,
            $this->em,
            $this->session,
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

        $this->api         = new DynamicsApi($this->integration);
    }

    public function testIntegration(): void
    {
        $this->assertSame('Dynamics', $this->integration->getName());
    }
}
