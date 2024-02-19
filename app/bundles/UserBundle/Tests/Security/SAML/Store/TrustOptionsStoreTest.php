<?php

namespace Mautic\UserBundle\Tests\Security\SAML\Store;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\UserBundle\Security\SAML\Store\TrustOptionsStore;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TrustOptionsStoreTest extends TestCase
{
    /**
     * @var CoreParametersHelper|MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $coreParametersHelper;

    private \Mautic\UserBundle\Security\SAML\Store\TrustOptionsStore $store;

    protected function setUp(): void
    {
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->store                = new TrustOptionsStore($this->coreParametersHelper, 'foobar');
    }

    public function testHasTrustOptionsIfSamlConfiguredAndEntityIdMatches(): void
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('saml_idp_metadata')
            ->willReturn('1');

        $this->assertTrue($this->store->has('foobar'));
    }

    public function testNotHaveTrustOptionsIfSamlDisabled(): void
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('saml_idp_metadata')
            ->willReturn('');

        $this->assertFalse($this->store->has('foobar'));
    }

    public function testNotHaveTrustOptionsIfEntityIdDoesNotMatch(): void
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('saml_idp_metadata')
            ->willReturn('1');

        $this->assertFalse($this->store->has('barfoo'));
    }

    public function testTrustOptionsDoNotSignRequestForDefault(): void
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('saml_idp_own_certificate')
            ->willReturn('');

        $store = $this->store->get('foobar');
        $this->assertFalse($store->getSignAuthnRequest());
    }

    public function testTrustOptionsSignRequestForCustom(): void
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('saml_idp_own_certificate')
            ->willReturn('abc');

        $store = $this->store->get('foobar');
        $this->assertTrue($store->getSignAuthnRequest());
    }
}
