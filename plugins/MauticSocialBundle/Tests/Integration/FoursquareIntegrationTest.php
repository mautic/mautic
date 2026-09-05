<?php

declare(strict_types=1);

namespace MauticPlugin\MauticSocialBundle\Tests\Integration;

use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Mautic\PluginBundle\Tests\Integration\AbstractIntegrationTestCase;
use MauticPlugin\MauticSocialBundle\Integration\FoursquareIntegration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FoursquareIntegration::class)]
#[AllowMockObjectsWithoutExpectations]
final class FoursquareIntegrationTest extends AbstractIntegrationTestCase
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
            $this->integrationEntityModel,
            $this->doNotContact,
            $this->fieldsWithUniqueIdentifier,
            new IdentifyCompanyHelper($this->companyModel, $this->createStub(CompanyLeadRepository::class)),
        );
    }

    public function testGetFormTypeReturnsNull(): void
    {
        // @phpstan-ignore-next-line - Intentional null check
        $this->assertNull($this->integration->getFormType());
    }
}
