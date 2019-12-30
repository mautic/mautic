<?php

/*
 * @copyright   2019 Mautic. All rights reserved
 * @author      Mautic.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MarketplaceBundle\Service;

use Symfony\Component\Filesystem\Filesystem;

class ComposerCombiner
{
    private $filesystem;
    private $mauticVersion;

    public function __construct(Filesystem $filesystem, string $mauticVersion = MAUTIC_VERSION)
    {
        $this->filesystem    = $filesystem;
        $this->mauticVersion = $mauticVersion;
    }

    /**
     * Tells Composer to use composer-combined.json instead of composer.json.
     * It will create the file if it does not exist.
     */
    public function useComposerCombinedJson(): void
    {
        $mauticRootDir = MAUTIC_ROOT_DIR;

        // Tell Composer to use composer-combined.json instead of composer.json.
        putenv('COMPOSER=composer-combined.json');
        putenv("COMPOSER_HOME={$mauticRootDir}");

        $composerCombinedPath = "{$mauticRootDir}/composer-combined.json";

        if ($this->filesystem->exists($composerCombinedPath)) {
            return;
        }

        // If the composer-combined.json file does not exist yet, copy it from composer.json if that file exists.
        if ($this->filesystem->exists("{$mauticRootDir}/composer.json")) {
            $this->filesystem->copy("{$mauticRootDir}/composer.json", $composerCombinedPath);

            return;
        }

        // If composer.json does not exist locally, download it from Github.
        $remoteComposerJson  = "https://github.com/mautic/mautic/blob/{$this->mauticVersion}/composer.json";
        $composerJsonContent = file_get_contents($remoteComposerJson);

        if (empty($composerJsonContent)) {
            throw new \Exception("Composer.json content was not possible to download from {$remoteComposerJson}");
        }

        $this->filesystem->dumpFile($composerCombinedPath, $composerJsonContent);
    }
}
