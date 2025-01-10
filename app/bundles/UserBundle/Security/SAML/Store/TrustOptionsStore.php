<?php

namespace Mautic\UserBundle\Security\SAML\Store;

use LightSaml\Meta\TrustOptions\TrustOptions;
use LightSaml\Store\TrustOptions\TrustOptionsStoreInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;

class TrustOptionsStore implements TrustOptionsStoreInterface
{
    private ?TrustOptions $trustOptions = null;

    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
        private string $entityId,
    ) {
    }

    public function get($entityId): TrustOptions
    {
        if ($this->trustOptions) {
            return $this->trustOptions;
        }

        return $this->createTrustOptions();
    }

    public function has($entityId): bool
    {
        // SAML is not enabled
        if (!$this->coreParametersHelper->get('saml_idp_metadata')) {
            return false;
        }

        // EntityIds do not match
        if ($entityId !== $this->entityId) {
            return false;
        }

        return true;
    }

    private function createTrustOptions(): TrustOptions
    {
        $this->trustOptions = $trustOptions = new TrustOptions();

        if (!$this->coreParametersHelper->get('saml_idp_own_certificate')) {
            return $trustOptions;
        }

        $trustOptions->setSignAuthnRequest(true);
        $trustOptions->setEncryptAssertions(true);
        $trustOptions->setEncryptAuthnRequest(true);
        $trustOptions->setSignAssertions(true);
        $trustOptions->setSignResponse(true);

        return $trustOptions;
    }
}
