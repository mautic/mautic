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

use Composer\Package\Package as ComposerPackage;

class Package extends ComposerPackage
{
    public function getInstallDirName(): string
    {
        if (!empty($this->getExtra()['install-directory-name'])) {
            return $this->getExtra()['install-directory-name'];
        }

        return $this->toCamelCase($this->getNameWithoutVendorPrefix());
    }

    public function getNameWithoutVendorPrefix(): string
    {
        return explode('/', $this->getName())[1];
    }

    private function toCamelCase(string $packageName): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', basename($packageName))));
    }
}
