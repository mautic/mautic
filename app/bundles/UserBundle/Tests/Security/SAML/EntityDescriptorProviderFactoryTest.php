<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\SAML;

use LightSaml\Credential\X509Certificate;
use LightSaml\Credential\X509Credential;
use LightSaml\Store\Credential\CredentialStoreInterface;
use Mautic\UserBundle\Security\SAML\EntityDescriptorProviderFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

class EntityDescriptorProviderFactoryTest extends TestCase
{
    public function testBuild(): void
    {
        $router          = $this->createMock(RouterInterface::class);
        $credentialStore = $this->createMock(CredentialStoreInterface::class);
        $entityId        = 'https://example.com';
        $samlRoute       = '/saml/login';

        $router->expects($this->once())
            ->method('generate')
            ->with($samlRoute)
            ->willReturn($samlRoute);

        $credentialStore->expects($this->once())
            ->method('getByEntityId')
            ->with($entityId)
            ->willReturn([$credential = $this->createMock(X509Credential::class)]);

        $credential->expects($this->once())
            ->method('getCertificate')
            ->willReturn(new X509Certificate());

        $builder = EntityDescriptorProviderFactory::build(
            $entityId,
            $router,
            $samlRoute,
            $credentialStore
        );

        $entityDescriptor = $builder->get();

        Assert::assertCount(
            1,
            $entityDescriptor->getFirstSpSsoDescriptor()->getAllAssertionConsumerServicesByUrl('https://example.com/saml/login'),
            'When building the SpSsoDescriptor, it should add a single AssertionConsumerService with the correct url. '
        );

        Assert::assertEquals(
            $entityId,
            $entityDescriptor->getEntityID(),
            'The entity ID should be set to the passed entity ID'
        );
    }
}
