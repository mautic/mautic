<?php

namespace Mautic\CoreBundle\Twig\Helper;

use Mautic\CoreBundle\Helper\UrlHelper;

final class GravatarHelper
{
    public function getImage(string $email, string $size = '250', string $default = 'mp'): string
    {
        $url = 'https://www.gravatar.com/avatar/'.md5(strtolower(trim($email))).'?s='.$size;

        $default = (str_contains($default, '.') && !str_starts_with($default, 'http')) ? UrlHelper::rel2abs($default) : $default;

        return $url.('&d='.urlencode($default));
    }

    public function getName(): string
    {
        return 'gravatar';
    }
}
