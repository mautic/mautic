<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Tests\Integration;

use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Event\PluginIntegrationKeyEvent;
use Mautic\PluginBundle\PluginEvents;
use Mautic\PluginBundle\Tests\Integration\AbstractIntegrationTestCase;
use MauticPlugin\MauticCrmBundle\Integration\HubspotIntegration;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class HubspotIntegrationTest extends AbstractIntegrationTestCase
{
    /**
     * @var MockObject&UserHelper
     */
    private MockObject $userHelper;

    private HubspotIntegration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userHelper  = $this->createMock(UserHelper::class);
        $this->integration = new HubspotIntegration(
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
            $this->fieldsWithUniqueIdentifier,
            $this->userHelper
        );
    }

    public function testGetRequiredKeyFields(): void
    {
        self::assertSame([], $this->integration->getRequiredKeyFields());
    }

    public function testGetBearerTokenEmpty(): void
    {
        $event = $this->createMock(PluginIntegrationKeyEvent::class);
        $event->expects(self::once())
            ->method('getKeys')
            ->willReturn(['other' => 'data']);
        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                new PluginIntegrationKeyEvent($this->integration, [HubspotIntegration::ACCESS_KEY]),
                PluginEvents::PLUGIN_ON_INTEGRATION_KEYS_ENCRYPT
            )
            ->willReturn($event);

        $this->integration->encryptAndSetApiKeys([HubspotIntegration::ACCESS_KEY], $this->createMock(Integration::class));
        self::assertNull($this->integration->getBearerToken());
    }

    public function testGetBearerTokenSet(): void
    {
        $token = 'token';

        $event = $this->createMock(PluginIntegrationKeyEvent::class);
        $event->expects(self::once())
            ->method('getKeys')
            ->willReturn(['other' => 'data', HubspotIntegration::ACCESS_KEY => $token]);
        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                new PluginIntegrationKeyEvent($this->integration, [HubspotIntegration::ACCESS_KEY]),
                PluginEvents::PLUGIN_ON_INTEGRATION_KEYS_ENCRYPT
            )
            ->willReturn($event);

        $this->integration->encryptAndSetApiKeys([HubspotIntegration::ACCESS_KEY], $this->createMock(Integration::class));
        self::assertSame($token, $this->integration->getBearerToken());
    }

    public function testGetFormSettings(): void
    {
        self::assertSame(
            [
                'requires_callback'      => false,
                'requires_authorization' => false,
            ],
            $this->integration->getFormSettings()
        );
    }

    public function testGetAuthenticationTypeNoOauthToken(): void
    {
        $event = $this->createMock(PluginIntegrationKeyEvent::class);
        $event->expects(self::once())
            ->method('getKeys')
            ->willReturn(['other' => 'data']);
        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                new PluginIntegrationKeyEvent($this->integration, [HubspotIntegration::ACCESS_KEY]),
                PluginEvents::PLUGIN_ON_INTEGRATION_KEYS_ENCRYPT
            )
            ->willReturn($event);

        $this->integration->encryptAndSetApiKeys([HubspotIntegration::ACCESS_KEY], $this->createMock(Integration::class));
        self::assertSame('key', $this->integration->getAuthenticationType());
    }

    public function testGetAuthenticationTypeWithOauthToken(): void
    {
        $event = $this->createMock(PluginIntegrationKeyEvent::class);
        $event->expects(self::once())
            ->method('getKeys')
            ->willReturn(['other' => 'data', HubspotIntegration::ACCESS_KEY => 'token']);
        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                new PluginIntegrationKeyEvent($this->integration, [HubspotIntegration::ACCESS_KEY]),
                PluginEvents::PLUGIN_ON_INTEGRATION_KEYS_ENCRYPT
            )
            ->willReturn($event);

        $this->integration->encryptAndSetApiKeys([HubspotIntegration::ACCESS_KEY], $this->createMock(Integration::class));
        self::assertSame('oauth2', $this->integration->getAuthenticationType());
    }

    public function testIsAuthorizedNoOauthToken(): void
    {
        $event = $this->createMock(PluginIntegrationKeyEvent::class);
        $event->expects(self::once())
            ->method('getKeys')
            ->willReturn(['other' => 'data']);
        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                new PluginIntegrationKeyEvent($this->integration, [HubspotIntegration::ACCESS_KEY]),
                PluginEvents::PLUGIN_ON_INTEGRATION_KEYS_ENCRYPT
            )
            ->willReturn($event);

        $this->integration->encryptAndSetApiKeys([HubspotIntegration::ACCESS_KEY], $this->createMock(Integration::class));
        self::assertFalse($this->integration->isAuthorized());
    }

    public function testIsAuthorizedWithOauthToken(): void
    {
        $event = $this->createMock(PluginIntegrationKeyEvent::class);
        $event->expects(self::once())
            ->method('getKeys')
            ->willReturn(['other' => 'data', HubspotIntegration::ACCESS_KEY => 'token']);
        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                new PluginIntegrationKeyEvent($this->integration, [HubspotIntegration::ACCESS_KEY]),
                PluginEvents::PLUGIN_ON_INTEGRATION_KEYS_ENCRYPT
            )
            ->willReturn($event);

        $this->integration->encryptAndSetApiKeys([HubspotIntegration::ACCESS_KEY], $this->createMock(Integration::class));
        self::assertTrue($this->integration->isAuthorized());
    }

    public function testAppendToFormKeys(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::exactly(2))
            ->method('add')
            ->withConsecutive(
                [HubspotIntegration::ACCESS_KEY, TextType::class],
                [$this->integration->getApiKey(), TextType::class],
            );

        $this->integration->appendToForm($builder, [], 'keys');
    }

    public function testAppendToFormFeatures(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with('objects', ChoiceType::class);

        $this->integration->appendToForm($builder, [], 'features');
    }
}
