<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\SAML;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\HttpFoundation\RequestStack;

class Helper
{
    public function __construct(private CoreParametersHelper $coreParametersHelper, private RequestStack $request)
    {
    }

    public function isSamlSession(): bool
    {
        return $this->isSamlEnabled() && $this->request->getSession()->has('samlsso');
    }

    public function isSamlEnabled(): bool
    {
        return (bool) $this->coreParametersHelper->get('saml_idp_metadata');
    }
}
