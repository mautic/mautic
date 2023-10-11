<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Mautic\CoreBundle\Helper\Filesystem;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Symfony\Component\Finder\Finder;

final class Version20230922140148 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            /** @var PathsHelper $pathsHelper */
            $pathsHelper = $this->container->get('mautic.helper.paths');

            return !file_exists($pathsHelper->getThemesPath().'/deleted.txt');
        }, '"deleted.txt" file not present, migration not required.');
    }

    public function up(Schema $schema): void
    {
        /** @var PathsHelper $pathsHelper */
        $pathsHelper = $this->container->get('mautic.helper.paths');
        $themesPath  = $pathsHelper->getThemesPath();
        $oldFilename = $themesPath.'/deleted.txt';
        /** @var Filesystem $filesystem */
        $filesystem     = $this->container->get('mautic.filesystem');
        $oldFileContent = $filesystem->readFile($oldFilename);

        if (empty($oldFileContent)) {
            $filesystem->remove($oldFilename);

            return;
        }

        $newFilename = $themesPath.'/'.ThemeHelper::HIDDEN_THEMES_TXT;
        $filesystem->rename($oldFilename, $newFilename);

        $finder = new Finder();
        $finder->directories()->in($themesPath)->depth(0);
        $themes = [];

        foreach ($finder as $theme) {
            if (!$theme->isLink()) {
                $themes[] = $theme->getBasename();
            }
        }

        $deletedThemes  = array_map(static fn ($item) => trim($item), explode('|', $oldFileContent));
        $themesToRemove = array_intersect($deletedThemes, $themes);
        $diff           = array_filter(array_diff($deletedThemes, $themesToRemove));

        if (empty($diff)) {
            $filesystem->remove($newFilename);

            return;
        }

        $filesystem->dumpFile($newFilename, sprintf('|%s', implode('|', $diff)));
    }
}
