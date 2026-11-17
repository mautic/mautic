<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Tests\Unit\Integration;

use Mautic\IntegrationsBundle\Exception\PushContactException;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeaturesInterface;
use Mautic\IntegrationsBundle\Sync\SyncService\SyncServiceInterface;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticCrmBundle\Form\Type\HubspotFeatureSettingsType;
use MauticPlugin\MauticCrmBundle\Form\Type\HubspotKeysType;
use MauticPlugin\MauticCrmBundle\Hubspot\Client\HubspotClient;
use MauticPlugin\MauticCrmBundle\Integration\HubspotIntegration;
use MauticPlugin\MauticCrmBundle\Integration\Support\HubspotConfigSupport;
use MauticPlugin\MauticCrmBundle\Tests\Unit\Hubspot\HubspotApiFake;
use MauticPlugin\MauticCrmBundle\Tests\Unit\Hubspot\HubspotTestTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class HubspotIntegrationTest extends TestCase
{
    use HubspotTestTrait;

    public function testPushNeedsASavedContact(): void
    {
        $this->expectException(PushContactException::class);
        $this->expectExceptionMessage('mautic.hubspot.push.not_saved');

        $this->integration()->pushContact(new Lead(), []);
    }

    public function testPushNeedsContactsEnabledInTheSync(): void
    {
        $this->expectException(PushContactException::class);
        $this->expectExceptionMessage('mautic.hubspot.push.contacts_not_synced');

        $this->integration([HubspotClient::COMPANIES])->pushContact($this->savedContact(), []);
    }

    public function testPushReportsAFailedSync(): void
    {
        $syncService = $this->createStub(SyncServiceInterface::class);
        $syncService->method('processIntegrationSync')->willThrowException(new \RuntimeException('Mapping is invalid'));

        $this->expectException(PushContactException::class);
        $this->expectExceptionMessage('Mapping is invalid');

        $this->integration([HubspotClient::CONTACTS], $syncService)->pushContact($this->savedContact(), []);
    }

    public function testConfigSupportForms(): void
    {
        $config     = $this->config();
        $client     = new HubspotApiFake()->client($config);
        $support    = new HubspotConfigSupport(fn (): SyncServiceInterface => $this->createStub(SyncServiceInterface::class), $config, $this->translator(), $this->fieldRepository($client));

        $this->assertSame(HubspotIntegration::NAME, $support->getName());
        $this->assertSame(HubspotKeysType::class, $support->getAuthConfigFormName());
        $this->assertSame(HubspotFeatureSettingsType::class, $support->getFeatureSettingsConfigFormName());
        $this->assertSame([ConfigFormFeaturesInterface::FEATURE_SYNC, ConfigFormFeaturesInterface::FEATURE_PUSH_LEAD], array_keys($support->getSupportedFeatures()));
        $this->assertSame(['email'], array_keys($support->getRequiredFieldsForMapping(HubspotClient::CONTACTS)));
        // required first, then the optional ones by label; hidden properties are left out
        $this->assertSame(['email', 'firstname', 'interests', 'hs_analytics_source'], array_keys($support->getAllFieldsForMapping(HubspotClient::CONTACTS)));
    }

    /**
     * @param string[] $objects
     */
    private function integration(array $objects = [HubspotClient::CONTACTS], ?SyncServiceInterface $syncService = null): HubspotIntegration
    {
        $syncService ??= $this->createStub(SyncServiceInterface::class);

        return new HubspotIntegration(static fn (): SyncServiceInterface => $syncService, $this->config($objects), $this->translator());
    }

    private function savedContact(): Lead
    {
        $contact = new Lead();
        $contact->setId(12);

        return $contact;
    }

    private function translator(): TranslatorInterface
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        return $translator;
    }
}
