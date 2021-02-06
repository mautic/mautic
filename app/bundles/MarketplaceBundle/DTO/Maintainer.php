<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\DTO;

class Maintainer
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
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
