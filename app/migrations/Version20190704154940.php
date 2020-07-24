<?php

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Finder\Finder;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190704154940 extends AbstractMauticMigration
{
    public function up(Schema $schema): void
    {
        /** @var PathsHelper $pathsHelper */
        $pathsHelper  = $this->container->get('mautic.helper.paths');
        $translations = $pathsHelper->getSystemPath('translations_root').'/translations';

        // Convert language config.php to config.json
        $languages = new Finder();
        $languages->directories()->in($translations);

        foreach ($languages as $lang) {
            $path = $lang->getPathname();

            if (file_exists($path.'/config.json')) {
                continue;
            }

            if (!file_exists($path.'/config.php')) {
                continue;
            }

            $config = include $path.'/config.php';
            file_put_contents($path.'/config.json', json_encode($config));

            @unlink($path.'/config.php');
        }
    }

    public function down(Schema $schema): void
    {
        /** @var PathsHelper $pathsHelper */
        $pathsHelper  = $this->container->get('mautic.helper.paths');
        $translations = $pathsHelper->getSystemPath('translations_root').'/translations';

        // Convert language config.php to config.json
        $languages = new Finder();
        $languages->directories()->in($translations);

        foreach ($languages as $lang) {
            $path = $lang->getPathname();

            if (file_exists($path.'/config.php')) {
                continue;
            }

            if (!file_exists($path.'/config.json')) {
                continue;
            }

            $config = json_decode(file_get_contents($path.'/config.json'), true);
            file_put_contents($path.'/config.php', "<?php\n return ".var_export($config, true).';');

            @unlink($path.'/config.json');
        }
    }
}
