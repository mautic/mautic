<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Service;

use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;

class LocalFileAdapterService extends LocalFilesystemAdapter
{
    public function __construct(string $root)
    {
        parent::__construct(
            $root,
            PortableVisibilityConverter::fromArray(
                [
                    'file' => [
                        'public'  => 0666,
                        'private' => 0600,
                    ],
                    'dir'  => [
                        'public'  => 0777,
                        'private' => 0700,
                    ],
                ]
            ),
            LOCK_EX,
            self::DISALLOW_LINKS
        );
    }
}
