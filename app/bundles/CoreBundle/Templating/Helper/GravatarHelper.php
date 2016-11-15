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

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\UrlHelper;
use Mautic\LeadBundle\Templating\Helper\AvatarHelper;
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
     * @var
     */
    private $imageDir;

    /**
     * @var AssetsHelper
     */
    private $assetHelper;

    /**
     * @var AvatarHelper
     */
    private $avatarHelper;

    /**
     * @var null|\Symfony\Component\HttpFoundation\Request
     */
    private $request;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->devMode      = $factory->getEnvironment() == 'dev';
        $this->imageDir     = $factory->getSystemPath('images');
        $this->assetHelper  = $factory->getHelper('template.assets');
        $this->avatarHelper = $factory->getHelper('template.avatar');
        $this->request      = $factory->getRequest();
        $this->devHosts     = (array) $factory->getParameter('dev_hosts');
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
        $localDefault = ($this->devMode || in_array($this->request->getClientIp(), array_merge($this->devHosts, ['127.0.0.1', 'fe80::1', '::1']))) ?
            'https://www.mautic.org/media/images/default_avatar.png' :
            $this->avatarHelper->getDefaultAvatar(true);
        $url = 'https://www.gravatar.com/avatar/'.md5(strtolower(trim($email))).'?s='.$size;

        if ($default === null) {
            $default = $localDefault;
        }

        $default = (strpos($default, '.') !== false && strpos($default, 'http') !== 0) ? UrlHelper::rel2abs($default) : $default;
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
