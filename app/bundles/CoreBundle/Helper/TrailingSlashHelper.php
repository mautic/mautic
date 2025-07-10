<?php

namespace Mautic\CoreBundle\Helper;

use Symfony\Component\HttpFoundation\Request;

class TrailingSlashHelper
{
    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public function getSafeRedirectUrl(Request $request): string
    {
        $siteUrl  = $this->coreParametersHelper->get('site_url');
        $pathInfo = substr($request->getPathInfo(), 0, -1);

        return rtrim($siteUrl, '/').'/'.ltrim($pathInfo, '/');
    }
}
