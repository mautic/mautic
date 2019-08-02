<?php

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Symfony\Component\HttpFoundation\Request;

class TrailingSlashHelper
{
    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * TrailingSlashHelper constructor.
     *
     * @param CoreParametersHelper $coreParametersHelper
     */
    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    public function getSafeRedirectUrl(Request $request)
    {
        $siteUrl  = $this->coreParametersHelper->getParameter('site_url');
        $pathInfo = substr($request->getPathInfo(), 0, -1);

        return $siteUrl.$pathInfo;
    }
}
