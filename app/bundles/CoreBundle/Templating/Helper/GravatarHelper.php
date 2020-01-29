<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UrlHelper;
use Mautic\LeadBundle\Templating\Helper\AvatarHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Templating\Helper\Helper;

/**
 * Class GravatarHelper.
 */
class GravatarHelper extends Helper
{
    /**
     * @var bool
     */
    private $devMode;

    /**
     * @var array
     */
    private $devHosts = [];

    /**
     * @var AvatarHelper
     */
    private $avatarHelper;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * GravatarHelper constructor.
     */
    public function __construct(
        PathsHelper $pathsHelper,
        AssetsHelper $assetHelper,
        AvatarHelper $avatarHelper,
        CoreParametersHelper $coreParametersHelper,
        RequestStack $requestStack
    ) {
        $this->devMode      = MAUTIC_ENV === 'dev';
        $pathsHelper->getSystemPath('images');
        $this->avatarHelper = $avatarHelper;
        $this->requestStack = $requestStack;
        $this->devHosts     = (array) $coreParametersHelper->getParameter('dev_hosts');
    }

    /**
     * @param string $email
     * @param string $size
     * @param string $default
     *
     * @return string
     */
    public function getImage($email, $size = '250', $default = null)
    {
        $request      = $this->requestStack->getCurrentRequest();
        $localDefault = ($this->devMode
            || ($request
                && in_array(
                    $request->getClientIp(),
                    array_merge($this->devHosts, ['127.0.0.1', 'fe80::1', '::1'])
                )))
            ?
            'https://www.mautic.org/media/images/default_avatar.png'
            :
            $this->avatarHelper->getDefaultAvatar(true);

        $url = 'https://www.gravatar.com/avatar/'.md5(strtolower(trim($email))).'?s='.$size;

        if (null === $default) {
            $default = $localDefault;
        }

        $default = (false !== strpos($default, '.') && 0 !== strpos($default, 'http')) ? UrlHelper::rel2abs($default) : $default;
        $url .= '&d='.urlencode($default);

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'gravatar';
    }
}
