<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Security\SAML\Store;

use LightSaml\Meta\TrustOptions\TrustOptions;
use LightSaml\Store\TrustOptions\TrustOptionsStoreInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;

class TrustOptionsStore implements TrustOptionsStoreInterface
{
    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var string
     */
    private $entityId;

    /**
     * @var TrustOptions
     */
    private $trustOptions;

    public function __construct(CoreParametersHelper $coreParametersHelper, string $entityId)
    {
        $this->coreParametersHelper = $coreParametersHelper;
        $this->entityId             = $entityId;
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
        if (!$this->coreParametersHelper->getParameter('saml_idp_metadata')) {
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

        $trustOptions->setSignAuthnRequest(true);
        $trustOptions->setEncryptAssertions(true);
        $trustOptions->setEncryptAuthnRequest(true);
        $trustOptions->setSignAssertions(true);
        $trustOptions->setSignResponse(true);

        return $trustOptions;
    }
}
