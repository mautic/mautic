<?php

/*
 * @copyright   2019 Mautic. All rights reserved
 * @author      Mautic.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MarketplaceBundle\DTO;

class Maintainer
{
    private $name;
    private $avatar;

    public function __construct(string $name, string $avatar)
    {
        $this->name   = $name;
        $this->avatar = $avatar;
    }

    public static function fromArray(array $array): Maintainer
    {
        return new self(
            $array['name'],
            $array['avatar_url']
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAvatar(): string
    {
        return $this->avatar;
    }
}
