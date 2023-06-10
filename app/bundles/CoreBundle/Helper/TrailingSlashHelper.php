<?php

namespace Mautic\CoreBundle\Helper;

use Symfony\Component\HttpFoundation\Request;

class TrailingSlashHelper
{
    /**
     * TrailingSlashHelper constructor.
     */
    public function __construct(private CoreParametersHelper $coreParametersHelper)
    {
    }

    /**
     * @return string
     */
    public function getSafeRedirectUrl(Request $request)
    {
        $siteUrl  = $this->coreParametersHelper->get('site_url');
        $pathInfo = substr($request->getPathInfo(), 0, -1);

        return $siteUrl.$pathInfo;
    }
}
