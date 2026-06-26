<?php

namespace MauticPlugin\MauticSocialBundle\Tests\Integration;

use Mautic\CoreBundle\Translation\Translator;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Tests\Integration\AbstractIntegrationTestCase;
use MauticPlugin\MauticSocialBundle\Integration\FoursquareIntegration;

#[\PHPUnit\Framework\Attributes\CoversClass(FoursquareIntegration::class)]
class FoursquareIntegrationTest extends AbstractIntegrationTestCase
{
    private FoursquareIntegration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->integration = new FoursquareIntegration(
            $this->dispatcher,
            $this->cache,
            $this->em,
            $this->request,
            $this->router,
            $this->createStub(Translator::class),
            $this->logger,
            $this->encryptionHelper,
            $this->leadModel,
            $this->companyModel,
            $this->pathsHelper,
            $this->notificationModel,
            $this->fieldModel,
            $this->fieldsWithUniqueIdentifier,
            $this->integrationEntityModel,
            $this->doNotContact,
            $this->createStub(IntegrationHelper::class),
        );
    }

    public function testGetFormTypeReturnsNull(): void
    {
        // @phpstan-ignore-next-line - Intentional null check
        $this->assertNull($this->integration->getFormType());
    }
}
