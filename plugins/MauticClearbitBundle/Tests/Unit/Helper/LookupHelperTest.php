<?php

declare(strict_types=1);

namespace MauticPlugin\MauticClearbitBundle\Tests\Unit\Helper;

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
use MauticPlugin\MauticClearbitBundle\Helper\LookupHelper;
use MauticPlugin\MauticClearbitBundle\Integration\ClearbitIntegration;
use MauticPlugin\MauticClearbitBundle\Services\Clearbit_Company;
use MauticPlugin\MauticClearbitBundle\Services\Clearbit_Person;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class LookupHelperTest extends TestCase
{
    private MockObject&IntegrationsHelper $integrationsHelper;

    private \PHPUnit\Framework\MockObject\Stub&UserHelper $userHelper;

    private \PHPUnit\Framework\MockObject\Stub&LoggerInterface $logger;

    private MockObject&LeadModel $leadModel;

    private MockObject&CompanyModel $companyModel;

    private MockObject&LeadRepository $leadRepository;

    private MockObject&CompanyRepository $companyRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->integrationsHelper = $this->createMock(IntegrationsHelper::class);
        $this->userHelper         = $this->createStub(UserHelper::class);
        $this->logger             = $this->createStub(LoggerInterface::class);
        $this->leadModel          = $this->createMock(LeadModel::class);
        $this->companyModel       = $this->createMock(CompanyModel::class);
        $this->leadRepository     = $this->createMock(LeadRepository::class);
        $this->companyRepository  = $this->createMock(CompanyRepository::class);
    }

    public function testConstructorLeavesIntegrationNullWhenNotFound(): void
    {
        $this->integrationsHelper->method('getIntegration')->with('Clearbit')
            ->willThrowException(new IntegrationNotFoundException());

        $helper = $this->makeHelper();

        $this->assertFalse($this->invokeGetClearbit($helper));
    }

    public function testGetClearbitReturnsFalseWhenIntegrationNotPublished(): void
    {
        $this->integrationsHelper->method('getIntegration')->with('Clearbit')
            ->willReturn($this->makeIntegration(false));

        $helper = $this->makeHelper();

        $this->assertFalse($this->invokeGetClearbit($helper));
    }

    public function testGetClearbitReturnsPersonInstanceWhenPublished(): void
    {
        $this->integrationsHelper->method('getIntegration')->with('Clearbit')
            ->willReturn($this->makeIntegration(true, ['apikey' => 'abc123']));

        $helper = $this->makeHelper();

        $this->assertInstanceOf(Clearbit_Person::class, $this->invokeGetClearbit($helper, true));
    }

    public function testGetClearbitReturnsCompanyInstanceWhenPublishedAndPersonFalse(): void
    {
        $this->integrationsHelper->method('getIntegration')->with('Clearbit')
            ->willReturn($this->makeIntegration(true, ['apikey' => 'abc123']));

        $helper = $this->makeHelper();

        $this->assertInstanceOf(Clearbit_Company::class, $this->invokeGetClearbit($helper, false));
    }

    public function testLookupContactReturnsEarlyWhenLeadHasNoEmail(): void
    {
        $this->integrationsHelper->method('getIntegration')->with('Clearbit')
            ->willReturn($this->makeIntegration(true, ['apikey' => 'abc123']));

        $lead = $this->createMock(Lead::class);
        $lead->method('getEmail')->willReturn(null);

        $this->leadModel->expects($this->never())->method('saveEntity');
        $this->leadRepository->expects($this->never())->method('saveEntity');

        $this->makeHelper()->lookupContact($lead);
    }

    public function testLookupContactSkipsLookupWhenCheckAutoAndAutoUpdateDisabled(): void
    {
        $this->integrationsHelper->method('getIntegration')->with('Clearbit')
            ->willReturn($this->makeIntegration(true, ['apikey' => 'abc123', 'auto_update' => '0']));

        $lead = $this->createMock(Lead::class);
        $lead->method('getEmail')->willReturn('john@example.com');

        $this->leadModel->expects($this->never())->method('saveEntity');
        $this->leadRepository->expects($this->never())->method('saveEntity');

        $this->makeHelper()->lookupContact($lead, false, true);
    }

    public function testLookupCompanyReturnsEarlyWhenNoWebsite(): void
    {
        $this->integrationsHelper->method('getIntegration')->with('Clearbit')
            ->willReturn($this->makeIntegration(true, ['apikey' => 'abc123']));

        $company = $this->createMock(Company::class);
        $company->method('getFieldValue')->with('companywebsite')->willReturn(null);

        $this->companyModel->expects($this->never())->method('saveEntity');
        $this->companyRepository->expects($this->never())->method('saveEntity');

        $this->makeHelper()->lookupCompany($company);
    }

    public function testValidateRequestReturnsFalseWhenNonceDoesNotMatch(): void
    {
        $this->integrationsHelper->method('getIntegration')->with('Clearbit')
            ->willThrowException(new IntegrationNotFoundException());

        $lead = new Lead();
        $lead->setId(7);
        $lead->setSocialCache(['clearbit' => ['clearbit#7#2026072612' => 'x', 'nonce' => 'right-nonce']]);

        $this->leadModel->method('getEntity')->with('7')->willReturn($lead);

        $result = $this->makeHelper()->validateRequest('clearbit#7#2026072612#3#wrong-nonce', 'person');

        $this->assertFalse($result);
    }

    public function testValidateRequestReturnsEntityWhenNonceMatches(): void
    {
        $this->integrationsHelper->method('getIntegration')->with('Clearbit')
            ->willThrowException(new IntegrationNotFoundException());

        $lead = new Lead();
        $lead->setId(7);
        $lead->setSocialCache(['clearbit' => ['clearbit_notify#7#2026072612' => 'x', 'nonce' => 'the-nonce']]);

        $this->leadModel->method('getEntity')->with('7')->willReturn($lead);

        $result = $this->makeHelper()->validateRequest('clearbit_notify#7#2026072612#3#the-nonce', 'person');

        $this->assertIsArray($result);
        $this->assertSame($lead, $result['entity']);
        $this->assertSame('3', $result['notify']);
    }

    public function testValidateRequestReturnsFalseWhenEntityNotFound(): void
    {
        $this->integrationsHelper->method('getIntegration')->with('Clearbit')
            ->willThrowException(new IntegrationNotFoundException());

        $this->leadModel->method('getEntity')->with('99')->willReturn(null);

        $result = $this->makeHelper()->validateRequest('clearbit#99#2026072612#3#some-nonce', 'person');

        $this->assertFalse($result);
    }

    private function makeHelper(): LookupHelper
    {
        return new LookupHelper(
            $this->integrationsHelper,
            $this->userHelper,
            $this->logger,
            $this->leadModel,
            $this->companyModel,
            $this->leadRepository,
            $this->companyRepository,
        );
    }

    /**
     * @param array<string, mixed> $apiKeys
     */
    private function makeIntegration(bool $isPublished, array $apiKeys = []): ClearbitIntegration
    {
        $configuration = new Integration();
        $configuration->setIsPublished($isPublished);
        $configuration->setApiKeys($apiKeys);

        $integration = new ClearbitIntegration();
        $integration->setIntegrationConfiguration($configuration);

        return $integration;
    }

    private function invokeGetClearbit(LookupHelper $helper, bool $person = true): false|Clearbit_Person|Clearbit_Company
    {
        $method = new \ReflectionMethod($helper, 'getClearbit');

        return $method->invoke($helper, $person);
    }
}
