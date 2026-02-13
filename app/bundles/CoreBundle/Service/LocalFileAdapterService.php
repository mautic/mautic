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
     * Override to ensure correct directory permissions are applied.
     *
     * Flysystem's LocalFilesystemAdapter::createDirectory() doesn't call chmod() after
     * mkdir(), which means the directory gets created with permissions affected by umask.
     * We explicitly set visibility after creation to ensure correct permissions.
     *
     * @see https://github.com/thephpleague/flysystem/issues/1584#issuecomment-1527372297
     */
    public function createDirectory(string $dirname, Config $config): void
    {
        parent::createDirectory($dirname, $config);

        // Explicitly set visibility to ensure correct permissions via chmod()
        $visibility = $config->get(Config::OPTION_VISIBILITY, $config->get(Config::OPTION_DIRECTORY_VISIBILITY));
        if (null === $visibility) {
            $visibility = Visibility::PUBLIC;
        }

        $this->setVisibility($dirname, $visibility);
    }
}
