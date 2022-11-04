<?php

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\Exception\BadConfigurationException;
use Mautic\CoreBundle\Factory\TransifexFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\Transifex\Connector\Statistics;
use Mautic\Transifex\Connector\Translations;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CLI Command to pull language resources from Transifex.
 */
class PullTransifexCommand extends Command
{
    private TransifexFactory $transifexFactory;
    private TranslatorInterface $translator;
    private PathsHelper $pathsHelper;
    private CoreParametersHelper $coreParametersHelper;

    public function __construct(
        TransifexFactory $transifexFactory,
        TranslatorInterface $translator,
        PathsHelper $pathsHelper,
        CoreParametersHelper $coreParametersHelper
    ) {
        $this->transifexFactory     = $transifexFactory;
        $this->translator           = $translator;
        $this->pathsHelper          = $pathsHelper;
        $this->coreParametersHelper = $coreParametersHelper;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('mautic:transifex:pull')
            ->setDescription('Fetches translations for Mautic from Transifex')
            ->addOption('language', null, InputOption::VALUE_OPTIONAL, 'Optional language to pull', null)
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command is used to retrieve updated Mautic translations from Transifex and writes them to the filesystem.

<info>php %command.full_name%</info>

The command can optionally only pull files for a specific language with the --language option

<info>php %command.full_name% --language=<language_code></info>
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $options        = $input->getOptions();
        $languageFilter = $options['language'];
        $files          = $this->getLanguageFiles();
        $translationDir = $this->pathsHelper->getTranslationsPath().'/';

        try {
            $transifex = $this->transifexFactory->getTransifex();
        } catch (BadConfigurationException $e) {
            $output->writeln($this->translator->trans('mautic.core.command.transifex_no_credentials'));

            return 1;
        }

        foreach ($files as $bundle => $stringFiles) {
            foreach ($stringFiles as $file) {
                $name  = $bundle.' '.str_replace('.ini', '', basename($file));
                $alias = $this->stringURLUnicodeSlug($name);
                $output->writeln($this->translator->trans('mautic.core.command.transifex_processing_resource', ['%resource%' => $name]));

                try {
                    /** @var Statistics $statisticsConnector */
                    $statisticsConnector = $transifex->get('statistics');
                    $response            = $statisticsConnector->getStatistics('mautic', $alias);
                    $languageStats       = json_decode((string) $response->getBody(), true);

                    foreach ($languageStats as $language => $stats) {
                        if ('en' == $language) {
                            continue;
                        }

                        // If we are filtering on a specific language, skip anything that doesn't match
                        if ($languageFilter && $languageFilter != $language) {
                            continue;
                        }

                        $output->writeln($this->translator->trans('mautic.core.command.transifex_processing_language', ['%language%' => $language]));

                        $completed = str_replace('%', '', $stats['completed']);

                        // We only want resources which are 80% completed
                        if ($completed >= 80) {
                            /** @var Translations $translationsConnector */
                            $translationsConnector = $transifex->get('translations');
                            $response              = $translationsConnector->getTranslation('mautic', $alias, $language);
                            $translation           = json_decode((string) $response->getBody(), true);
                            $path                  = $translationDir.$language.'/'.$bundle.'/'.basename($file);

                            // Verify the directories exist
                            if (!is_dir($translationDir.$language)) {
                                if (!mkdir($translationDir.$language)) {
                                    $output->writeln(
                                        $this->translator->trans('mautic.core.command.transifex_error_creating_directory', [
                                            '%directory%' => $translationDir.$language,
                                            '%language%'  => $language,
                                        ]));

                                    continue;
                                }
                            }

                            if (!is_dir($translationDir.$language.'/'.$bundle)) {
                                if (!mkdir($translationDir.$language.'/'.$bundle)) {
                                    $output->writeln(
                                        $this->translator->trans('mautic.core.command.transifex_error_creating_directory', [
                                            '%directory%' => $translationDir.$language.'/'.$bundle,
                                            '%language%'  => $language,
                                        ]));

                                    continue;
                                }
                            }

                            // Write the file to the system
                            if (!file_put_contents($path, $translation['content'])) {
                                $output->writeln(
                                    $this->translator->trans('mautic.core.command.transifex_error_creating_file',
                                        ['%file%' => $path, '%language%' => $language]
                                    )
                                );

                                continue;
                            }

                            $output->writeln($this->translator->trans('mautic.core.command.transifex_resource_downloaded'));
                        }
                    }
                } catch (\Exception $exception) {
                    $output->writeln($this->translator->trans('mautic.core.command.transifex_error_pulling_data', ['%message%' => $exception->getMessage()]));
                }
            }
        }

        return 0;
    }

    /**
     * Returns Mautic translation files.
     *
     * @return array<string,string[]>
     */
    private function getLanguageFiles(): array
    {
        $files         = [];
        $mauticBundles = $this->coreParametersHelper->get('bundles');
        $pluginBundles = $this->coreParametersHelper->get('plugin.bundles');

        foreach ($mauticBundles as $bundle) {
            // Parse the namespace into a filepath
            $translationsDir = $bundle['directory'].'/Translations/en_US';

            if (is_dir($translationsDir)) {
                $files[$bundle['bundle']] = [];

                // Get files within the directory
                $finder = new Finder();
                $finder->files()->in($translationsDir)->name('*.ini');

                /** @var \Symfony\Component\Finder\SplFileInfo $file */
                foreach ($finder as $file) {
                    $files[$bundle['bundle']][] = $file->getPathname();
                }
            }
        }

        foreach ($pluginBundles as $bundle) {
            // Parse the namespace into a filepath
            $translationsDir = $bundle['directory'].'/Translations/en_US';

            if (is_dir($translationsDir)) {
                $files[$bundle['bundle']] = [];

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
