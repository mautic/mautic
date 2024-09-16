<?php

namespace Mautic\UserBundle\Tests\Security\SAML\Store;

use LightSaml\Meta\TrustOptions\TrustOptions;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\UserBundle\Security\SAML\Store\TrustOptionsStore;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TrustOptionsStoreTest extends TestCase
{  /**
 * @var CoreParametersHelper|MockObject
 */
    private $coreParametersHelper;

    /**
     * @var TrustOptionsStore
     */
    private $store;

    protected function setUp(): void
    {
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->store                = new TrustOptionsStore($this->coreParametersHelper, 'foobar');
    }

    public function testTrustOptionsConfiguredIfSamlEnabledAndEntityIdMatches()
    {
        $store = $this->store->get('foobar');
        $this->assertInstanceOf(TrustOptions::class, $store);
    }

    public function testHasTrustOptionsIfSamlConfiguredAndEntityIdMatches()
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('saml_idp_metadata')
            ->willReturn('1');

        $this->assertTrue($this->store->has('foobar'));
    }

    public function testNotHaveTrustOptionsIfSamlDisabled()
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('saml_idp_metadata')
            ->willReturn('');

        $this->assertFalse($this->store->has('foobar'));
    }

    public function testNotHaveTrustOptionsIfEntityIdDoesNotMatch()
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('saml_idp_metadata')
            ->willReturn('1');

        $this->assertFalse($this->store->has('barfoo'));
    }

    public function testTrustOptionsDoNotSignRequestForDefault()
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('saml_idp_own_certificate')
            ->willReturn('');

        $store = $this->store->get('foobar');
        $this->assertFalse($store->getSignAuthnRequest());
    }

    public function testTrustOptionsSignRequestForCustom()
    {
        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('saml_idp_own_certificate')
            ->willReturn('abc');

        $store = $this->store->get('foobar');
        $this->assertTrue($store->getSignAuthnRequest());
    }
}
