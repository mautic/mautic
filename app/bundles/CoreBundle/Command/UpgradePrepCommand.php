<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;

/**
 * CLI Command to generate production assets
 */
class UpgradePrepCommand extends ContainerAwareCommand
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('mautic:prepare:upgrade')
            ->setDescription('Prepares your 1.3.x installation for upgrading to Mautic 2.0')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command prepares your 1.3.x installation for upgrading to Mautic 2.0.

<info>php %command.full_name%</info>
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $mauticRoot = $container->get('mautic.factory')->getSystemPath('root');
        $zip = new ZipArchive();

        if ($error = $zip->open($mauticRoot . '/mautic.zip', ZipArchive::CREATE) !== true) {
            throw new \Exception('Error creating zip file. ZipArchive error.');
        }

        $filesToAdd = array(
            'app/config/local.php',
            'app/config/*_local.php',
            'plugins',
            'themes',
            'translations',
            'media',
            $container->getParameter('mautic.mailer_spool_path'),
            $container->getParameter('mautic.upload_dir'),
            $container->getParameter('mautic.image_path')
        );

        $this->addFiles($filesToAdd, $zip);

        /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
        $translator = $this->getContainer()->get('translator');
        $translator->setLocale($this->getContainer()->get('mautic.factory')->getParameter('locale'));

        if ($zip->close()) {
            $output->writeln('<info>' . $translator->trans('mautic.core.command.prepare_upgrade_success') . '</info>');
            return 0;
        }

        $output->writeln('<error>' . $translator->trans('mautic.core.command.prepare_upgrade_fail') . '</error>');

        return 1;
    }

    /**
     * @param array $files
     * @param ZipArchive $zip
     */
    protected function addFiles(array $files, ZipArchive $zip)
    {
        $mauticRoot = $this->getContainer()->get('mautic.factory')->getSystemPath('root');

        foreach ($files as $file) {
            $localPath = $this->sanitizePath($file);
            $zipPath = $this->sanitizePath($localPath, true);

            if (is_dir($localPath)) {
                // Create the empty directory in the zip
                $zip->addEmptyDir($zipPath);

                // .htaccess is not caught by glob
                if (file_exists($localPath . '/.htaccess')) {
                    $zip->addFile($localPath . '/.htaccess', $zipPath . '/.htaccess');
                }

                $globbed = glob($localPath . '/*');

                if (! empty($globbed)) {
                    $this->addFiles($globbed, $zip);
                }

                continue;
            } else {
                if (strpos($localPath, '*') !== false) {
                    $globbed = glob($localPath);

                    $this->addFiles($globbed, $zip);
                } else {
                    $zip->addFile($localPath, $zipPath);
                }
            }
        }
    }

    /**
     * @param string $path
     * @param bool $forZip
     *
     * @return string
     */
    protected function sanitizePath($path, $forZip = false)
    {
        $mauticRoot = $this->getContainer()->get('mautic.factory')->getSystemPath('root');

        // Ensure a normalized root string
        $mauticRoot = rtrim($mauticRoot, '/');

        // Some declared paths have the mautic root, others do not. Normalize it.
        $path = str_replace($mauticRoot, '', $path);
        // Remove any slashes from the beginning of the path string
        $path = ltrim($path, '/');

        if ($forZip) {
            return $path;
        }

        // Rebuild the full path, including the mautic root
        $path = $mauticRoot . '/' . $path;

        return $path;
    }
}
