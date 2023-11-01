<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Service;

use League\Flysystem\Adapter\Local;

class LocalFileAdapterService extends Local
{
    /**
     * @var array<string, array<string, int>>
     */
    protected static $permissions = [
        'file' => [
            'public'  => 0666,
            'private' => 0600,
        ],
        'dir'  => [
            'public'  => 0777,
            'private' => 0700,
        ],
    ];

    public function __construct(string $root)
    {
        parent::__construct($root, LOCK_EX, self::DISALLOW_LINKS, self::$permissions);
    }
}
