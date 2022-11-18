<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\Factory\TransifexFactory;
use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UrlHelper;
use Mautic\Transifex\Connector\Statistics;
use Mautic\Transifex\Connector\Translations;
use Mautic\Transifex\Exception\InvalidConfigurationException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CLI Command to pull language resources from Transifex.
 */
class PullTransifexCommand extends Command
{
    private TransifexFactory $transifexFactory;
    private TranslatorInterface $translator;
    private PathsHelper $pathsHelper;
    private LanguageHelper $languageHelper;

    public function __construct(
        TransifexFactory $transifexFactory,
        TranslatorInterface $translator,
        PathsHelper $pathsHelper,
        LanguageHelper $languageHelper
    ) {
        $this->transifexFactory = $transifexFactory;
        $this->translator       = $translator;
        $this->pathsHelper      = $pathsHelper;
        $this->languageHelper   = $languageHelper;

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
        $languageFilter = $input->getOption('language');
        $files          = $this->languageHelper->getLanguageFiles();
        $translationDir = $this->pathsHelper->getTranslationsPath().'/';

        try {
            $transifex = $this->transifexFactory->getTransifex();
        } catch (InvalidConfigurationException $e) {
            $output->writeln($this->translator->trans('mautic.core.command.transifex_no_credentials'));

            return 1;
        }

        foreach ($files as $bundle => $stringFiles) {
            foreach ($stringFiles as $file) {
                $name  = $bundle.' '.str_replace('.ini', '', basename($file));
                $alias = UrlHelper::stringURLUnicodeSlug($name);
                $output->writeln($this->translator->trans('mautic.core.command.transifex_processing_resource', ['%resource%' => $name]));

                try {
                    $statisticsConnector = $transifex->get(Statistics::class);
                    \assert($statisticsConnector instanceof Statistics);

                    $translationsConnector = $transifex->get(Translations::class);
                    \assert($translationsConnector instanceof Translations);

                    $response      = $statisticsConnector->getStatistics($alias);
                    $languageStats = json_decode((string) $response->getBody(), true);

                    foreach ($languageStats['data'] as $stats) {
                        $language = ltrim($stats['relationships']['language']['data']['id'], 'l:');
                        if ('en' === $language) {
                            continue;
                        }

                        // If we are filtering on a specific language, skip anything that doesn't match
                        if ($languageFilter && $languageFilter != $language) {
                            continue;
                        }

                        $output->writeln($this->translator->trans('mautic.core.command.transifex_processing_language', ['%language%' => $language]));

                        $completed = $stats['attributes']['translated_strings'] / $stats['attributes']['total_strings'];

                        // We only want resources which are 80% completed
                        if ($completed >= 0.8) {
                            $response    = $translationsConnector->getTranslation($alias, $language);
                            $translation = json_decode((string) $response->getBody(), true);
                            $path        = $translationDir.$language.'/'.$bundle.'/'.basename($file);

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
}
