<?php

namespace MauticPlugin\MauticSocialBundle\Tests\Integration;

use Mautic\CoreBundle\Translation\Translator;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Tests\Integration\AbstractIntegrationTestCase;
use MauticPlugin\MauticSocialBundle\Integration\InstagramIntegration;

#[\PHPUnit\Framework\Attributes\CoversClass(InstagramIntegration::class)]
class InstagramIntegrationTest extends AbstractIntegrationTestCase
{
    private InstagramIntegration $integration;

    /**
     * @var Translator&\PHPUnit\Framework\MockObject\Stub
     */
    protected \PHPUnit\Framework\MockObject\Stub $coreTranslator;

    /**
     * @var IntegrationHelper&\PHPUnit\Framework\MockObject\Stub
     */
    protected \PHPUnit\Framework\MockObject\Stub $integrationHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coreTranslator    = $this->createStub(Translator::class);
        $this->integrationHelper = $this->createStub(IntegrationHelper::class);

        $this->integration = new InstagramIntegration(
            $this->dispatcher,
            $this->cache,
            $this->em,
            $this->request,
            $this->router,
            $this->coreTranslator,
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
            $this->integrationHelper,
        );
    }

    public function testGetFormTypeReturnsNull(): void
    {
        // @phpstan-ignore-next-line - Intentional null check
        $this->assertNull($this->integration->getFormType());
    }
}
