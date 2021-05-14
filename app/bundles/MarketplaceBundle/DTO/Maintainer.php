<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\DTO;

final class Maintainer
{
    public string $name;
    public string $avatar;

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
}
