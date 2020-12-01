<?php
/**
 * Created by PhpStorm.
 * User: Werner
 * Date: 6/20/2017
 * Time: 8:10 AM.
 */

namespace MauticPlugin\MauticCrmBundle\Tests;

use Mautic\PluginBundle\Tests\Integration\AbstractIntegrationTestCase;
use MauticPlugin\MauticCrmBundle\Integration\DynamicsIntegration;

class DynamicsIntegrationTest extends AbstractIntegrationTestCase
{
    /** @var DynamicsIntegration */
    private $integration;

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
            $this->doNotContact
        );
    }

    public function testIntegration()
    {
        $this->assertSame('Dynamics', $this->integration->getName());
    }
}
