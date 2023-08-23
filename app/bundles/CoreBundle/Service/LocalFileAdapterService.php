<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Service;

use League\Flysystem\Config;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;

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
                ],
                Visibility::PUBLIC
            ),
            LOCK_EX,
            self::DISALLOW_LINKS
        );
    }

    /**
     * @see https://github.com/thephpleague/flysystem/issues/1584#issuecomment-1527372297
     */
    public function createDirectory(string $dirname, Config $config): void
    {
        $umask = umask(0);

        parent::createDirectory($dirname, $config);

        umask($umask);
    }
}
