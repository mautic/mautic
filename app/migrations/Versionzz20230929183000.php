<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Helper\PathsHelper;

/**
 * Move config files that contain local config to a folder outside the application data.
 */
final class Versionzz20230929183000 extends AbstractMauticMigration
{
    public function preUp(Schema $schema): void
    {
        [$appConfigDir] = $this->getConfigDirs();

        $matches = glob($appConfigDir.'/*local.php');

        $this->skipIf(
            0 == count($matches),
            'There are no local config files to migrate. Skipping the migration.'
        );
    }

    public function up(Schema $schema): void
    {
        $pathsHelper = $this->container->get(PathsHelper::class);

        $appConfigDir   = $pathsHelper->getRootPath().'/app/config';
        $localConfigDir = $pathsHelper->getVendorRootPath().'/config';

        $matches = glob($appConfigDir.'/*local.php');

        foreach ($matches as $file) {
            rename($file, $localConfigDir.'/'.pathinfo($file, PATHINFO_BASENAME));
        }
    }

    /**
     * @return string[]
     */
    public function getConfigDirs(): array
    {
        $pathsHelper = $this->container->get(PathsHelper::class);

        $appConfigDir   = $pathsHelper->getRootPath().'/app/config';
        $localConfigDir = $pathsHelper->getVendorRootPath().'/config';

        return [$appConfigDir, $localConfigDir];
    }
}
