<?php
/*
 * @package     Cronfig Mautic Bundle
 * @copyright   2019 Cronfig.io. All rights reserved
 * @author      Jan Linhart
 * @link        http://cronfig.io
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MarketplaceBundle\Service;

use MauticPlugin\MarketplaceBundle\Api\Connection;
use MauticPlugin\MarketplaceBundle\DTO\Package;

class PluginDownloader
{
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function download(Package $package): void
    {
        $dirName    = $package->getInstallDirName();
        $pluginsDir = $this->getPluginDirectory();
        $pluginPath = $pluginsDir.$dirName;
        $tmpZipFile = $pluginsDir.'/_tmp_'.$dirName.'.zip';

        $this->connection->download($package->getDistUrl(), $tmpZipFile);

        $zip = new \ZipArchive();
        $res = $zip->open($tmpZipFile);

        if (true === $res) {
            $tmpPluginDirname = $zip->getNameIndex(0);
            $zip->extractTo($pluginsDir);
            $zip->close();
        } else {
            throw new \Exception("There was an error during unzipping {$tmpZipFile} into {$pluginsDir}");
        }

        $tmpPluginDir = $pluginsDir.'/'.$tmpPluginDirname;

        if (false === rename($tmpPluginDir, $pluginPath)) {
            throw new \Exception("There was an error during renaming {$tmpPluginDir} into {$pluginPath}");
        }

        if (false === unlink($tmpZipFile)) {
            throw new \Exception("There was an error during removing {$tmpZipFile}");
        }
    }

    public function getPluginDirectory(): string
    {
        return __DIR__.'/../../';
    }
}
