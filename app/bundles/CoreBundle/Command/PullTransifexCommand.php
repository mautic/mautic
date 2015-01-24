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
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * CLI Command to pull language resources from Transifex
 */
class PullTransifexCommand extends ContainerAwareCommand
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('mautic:transifex:pull')
            ->setDescription('Fetches translations for Mautic from Transifex')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command is used to retrieve updated Mautic translations from Transifex and writes them to the filesystem.

<info>php %command.full_name%</info>
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
        $translator = $this->getContainer()->get('translator');
        $translator->setLocale($this->getContainer()->get('mautic.factory')->getParameter('locale'));
        $username = $this->getContainer()->get('mautic.factory')->getParameter('transifex_username');
        $password = $this->getContainer()->get('mautic.factory')->getParameter('transifex_password');

        if (empty($username) || empty($password)) {
            $output->writeln($translator->trans('mautic.core.command.transifex_no_credentials'));

            return 0;
        }

        $options = $input->getOptions();
        $files   = $this->getLanguageFiles();

        /** @var \BabDev\Transifex\Transifex $transifex */
        $transifex = $this->getContainer()->get('transifex');

        foreach ($files as $bundle => $stringFiles) {
            foreach ($stringFiles as $file) {
                $name  = $bundle . ' ' . str_replace('.ini', '', basename($file));
                $alias = $this->stringURLUnicodeSlug($name);
                $output->writeln($translator->trans('mautic.core.command.transifex_processing_resource', array('%resource%' => $name)));

                try {
                    $languageStats = $transifex->statistics->getStatistics('mautic', $alias);

                    foreach ($languageStats as $language => $stats) {
                        if ($language == 'en') {
                            continue;
                        }

                        $output->writeln($translator->trans('mautic.core.command.transifex_processing_language', array('%language%' => $language)));

                        $completed = str_replace('%', '', $stats->completed);

                        // We only want resources which are 80% completed
                        if ($completed >= 80) {
                            $translation = $transifex->translations->getTranslation('mautic', $alias, $language);

                            $path = str_replace('en_US', $language, $file);

                            // Verify the language's directory exists
                            if (!is_dir(dirname($path))) {
                                if (!mkdir(dirname($path))) {
                                    $output->writeln(
                                        $translator->trans(
                                            'mautic.core.command.transifex_error_creating_directory',
                                            array('%directory%' => dirname($path), '%language%' => $language)
                                        )
                                    );

                                    continue;
                                }
                            }

                            // Write the file to the system
                            if (!file_put_contents($path, $translation->content)) {
                                $output->writeln(
                                    $translator->trans(
                                        'mautic.core.command.transifex_error_creating_file',
                                        array('%file%' => $path, '%language%' => $language)
                                    )
                                );

                                continue;
                            }

                            $output->writeln($translator->trans('mautic.core.command.transifex_resource_downloaded'));
                        }
                    }
                } catch (\Exception $exception) {
                    $output->writeln($translator->trans('mautic.core.command.transifex_error_pulling_data', array('%message%' => $exception->getMessage())));
                }
            }
        }

        return 0;
    }

    /**
     * Returns Mautic translation files
     *
     * @return array
     */
    private function getLanguageFiles()
    {
        $files = array();
        $mauticBundles = $this->getContainer()->getParameter('mautic.bundles');
        $addonBundles  = $this->getContainer()->getParameter('mautic.addon.bundles');

        foreach ($mauticBundles as $bundle) {
            // Parse the namespace into a filepath
            $translationsDir = $bundle['directory'] . '/Translations/en_US';

            if (is_dir($translationsDir)) {
                $files[$bundle['bundle']] = array();

                // Get files within the directory
                $finder = new Finder();
                $finder->files()->in($translationsDir)->name('*.ini');

                /** @var \Symfony\Component\Finder\SplFileInfo $file */
                foreach ($finder as $file) {
                    $files[$bundle['bundle']][] = $file->getPathname();
                }
            }
        }

        foreach ($addonBundles as $bundle) {
            // Parse the namespace into a filepath
            $translationsDir = $bundle['directory'] . '/Translations/en_US';

            if (is_dir($translationsDir)) {
                $files[$bundle['bundle']] = array();

                // Get files within the directory
                $finder = new Finder();
                $finder->files()->in($translationsDir)->name('*.ini');

                /** @var \Symfony\Component\Finder\SplFileInfo $file */
                foreach ($finder as $file) {
                    $files[$bundle['bundle']][] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    /**
     * This method implements unicode slugs instead of transliteration.
     *
     * @param string $string String to process
     *
     * @return string
     */
    public static function stringURLUnicodeSlug($string)
    {
        // Replace double byte whitespaces by single byte (East Asian languages)
        $str = preg_replace('/\xE3\x80\x80/', ' ', $string);

        // Remove any '-' from the string as they will be used as concatenator.
        // Would be great to let the spaces in but only Firefox is friendly with this
        $str = str_replace('-', ' ', $str);

        // Replace forbidden characters by whitespaces
        $str = preg_replace('#[:\#\*"@+=;!><&\.%()\]\/\'\\\\|\[]#', "\x20", $str);

        // Delete all '?'
        $str = str_replace('?', '', $str);

        // Trim white spaces at beginning and end of alias and make lowercase
        $str = trim(strtolower($str));

        // Remove any duplicate whitespace and replace whitespaces by hyphens
        $str = preg_replace('#\x20+#', '-', $str);

        return $str;
    }
}
