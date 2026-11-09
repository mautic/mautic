<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFullContactBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticFullContactBundle\Helper\LookupHelper;
use MauticPlugin\MauticFullContactBundle\Integration\FullContactIntegration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;

final class LookupHelperTest extends TestCase
{
    /**
     * Without a settings row, or while unpublished, FullContact is not configured: lookups do nothing
     * instead of failing. An automatic lookup also does nothing while auto update is off.
     */
    #[DataProvider('noLookupProvider')]
    public function testLookupsDoNothing(bool $hasSettingsRow, bool $isPublished, bool $checkAuto): void
    {
        $integrationsHelper = $this->createStub(IntegrationsHelper::class);
        if ($hasSettingsRow) {
            $settings = new Integration();
            $settings->setIsPublished($isPublished);
            $settings->setApiKeys(['apikey' => 'test-key', 'auto_update' => false]);
            $integration = new FullContactIntegration();
            $integration->setIntegrationConfiguration($settings);
            $integrationsHelper->method('getIntegration')->willReturn($integration);
        } else {
            $integrationsHelper->method('getIntegration')->willThrowException(new IntegrationNotFoundException());
        }

        $leadModel = $this->createMock(LeadModel::class);
        $leadModel->expects($this->never())->method('saveEntity');
        $companyModel = $this->createMock(CompanyModel::class);
        $companyModel->expects($this->never())->method('saveEntity');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('log');

        $helper = new LookupHelper(
            $integrationsHelper,
            $this->createStub(UserHelper::class),
            $logger,
            $this->createStub(RouterInterface::class),
            $leadModel,
            $companyModel,
            $this->createStub(LeadRepository::class),
            $this->createStub(CompanyRepository::class),
        );

        $lead = new Lead();
        $lead->setEmail('john@example.com');
        $helper->lookupContact($lead, false, $checkAuto);

        $company = new Company();
        $company->addUpdatedField('companywebsite', 'https://example.com');
        $helper->lookupCompany($company, false, $checkAuto);
    }

    /**
     * @return iterable<string, array{bool, bool, bool}>
     */
    public static function noLookupProvider(): iterable
    {
        yield 'no settings row' => [false, false, false];
        yield 'unpublished' => [true, false, false];
        yield 'automatic lookup with auto update off' => [true, true, true];
    }
}
