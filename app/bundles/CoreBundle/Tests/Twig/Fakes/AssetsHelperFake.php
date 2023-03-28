<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Twig\Fakes;

class AssetsHelperFake
{
    public function __construct()
    {
    }

    public function getAssetPrefix($includeEndingSlash = false): string
    {
        return 'assetPrefix';
    }

    public function getUrl($path, $packageName = null, $version = null, $absolute = false, $ignorePrefix = false): string
    {
        return "https://example.com/{$path}/{$packageName}/{$version}/{$absolute}/{$ignorePrefix}}";
    }
}
