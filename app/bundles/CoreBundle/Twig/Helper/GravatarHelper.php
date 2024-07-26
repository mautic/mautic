<?php

namespace Mautic\CoreBundle\Twig\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UrlHelper;
use Mautic\LeadBundle\Twig\Helper\DefaultAvatarHelper;
use Symfony\Component\HttpFoundation\RequestStack;

final class GravatarHelper
{
    private bool $devMode;

    /**
     * @var array<string>
     */
    private array $devHosts;

    public function __construct(
        private DefaultAvatarHelper $defaultAvatarHelper,
        CoreParametersHelper $coreParametersHelper,
        private RequestStack $requestStack
    ) {
        $this->devMode             = MAUTIC_ENV === 'dev';
        $this->devHosts            = (array) $coreParametersHelper->get('dev_hosts');
    }

    /**
     * @param string $email
     * @param string $size
     * @param string $default
     */
    public function getImage($email, $size = '250', $default = null): string
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

        $default = (str_contains($default, '.') && !str_starts_with($default, 'http')) ? UrlHelper::rel2abs($default) : $default;

        return $url.('&d='.urlencode($default));
    }

    public function getName(): string
    {
        return 'gravatar';
    }
}
