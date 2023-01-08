<?php

namespace Mautic\CoreBundle\Templating\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UrlHelper;
use Mautic\LeadBundle\Templating\Helper\DefaultAvatarHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Templating\Helper\Helper;

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
     * @var DefaultAvatarHelper
     */
    private $defaultAvatarHelper;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(
        DefaultAvatarHelper $defaultAvatarHelper,
        CoreParametersHelper $coreParametersHelper,
        RequestStack $requestStack
    ) {
        $this->devMode             = MAUTIC_ENV === 'dev';
        $this->defaultAvatarHelper = $defaultAvatarHelper;
        $this->requestStack        = $requestStack;
        $this->devHosts            = (array) $coreParametersHelper->get('dev_hosts');
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
            'mp'
            :
            $this->defaultAvatarHelper->getDefaultAvatar(true);

        $url = 'https://www.gravatar.com/avatar/'.md5(strtolower(trim($email))).'?s='.$size;

        if (null === $default) {
            $default = $localDefault;
        }

        $default = (false !== strpos($default, '.') && 0 !== strpos($default, 'http')) ? UrlHelper::rel2abs($default) : $default;

        return $url.('&d='.urlencode($default));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'gravatar';
    }
}
