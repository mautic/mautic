<?php

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\Exception\BadConfigurationException;
use Mautic\CoreBundle\Factory\TransifexFactory;
use Mautic\Transifex\Connector\Resources;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * CLI Command to push language resources to Transifex.
 */
class PushTransifexCommand extends ContainerAwareCommand
{
    private $transifexFactory;
    private $translator;

    public function __construct(
        TransifexFactory $transifexFactory,
        TranslatorInterface $translator
    ) {
        $this->transifexFactory = $transifexFactory;
        $this->translator       = $translator;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('mautic:transifex:push')
            ->setDescription('Pushes Mautic translation resources to Transifex')
            ->setDefinition([
                new InputOption(
                    'create', null, InputOption::VALUE_NONE,
                    'Flag to create new resources.'
                ),
            ])
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command is used to push translation resources to Transifex

<info>php %command.full_name%</info>

You can optionally choose to create new resources with the --create option:

<info>php %command.full_name% --create</info>
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->translator->setLocale($this->getContainer()->get('mautic.factory')->getParameter('locale'));

        $options = $input->getOptions();
        $create  = $options['create'];
        $files   = $this->getLanguageFiles();

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

                /** @var Resources $resourcesConnector */
                $resourcesConnector = $transifex->get('resources');

                try {
                    if ($create) {
                        $resourcesConnector->createResource('mautic', $name, $alias, 'PHP_INI', ['file' => $file]);
                        $output->writeln($this->translator->trans('mautic.core.command.transifex_resource_created'));
                    } else {
                        $resourcesConnector->updateResourceContent('mautic', $alias, $file, 'file');
                        $output->writeln($this->translator->trans('mautic.core.command.transifex_resource_updated'));
                    }
                } catch (\Exception $exception) {
                    $output->writeln($this->translator->trans('mautic.core.command.transifex_error_pushing_data', ['%message%' => $exception->getMessage()]));
                }
            }
        }

        return 0;
    }

    /**
     * Returns Mautic translation files.
     *
     * @return array
     */
    private function getLanguageFiles()
    {
        $files         = [];
        $mauticBundles = $this->getContainer()->getParameter('mautic.bundles');
        $pluginBundles = $this->getContainer()->getParameter('mautic.plugin.bundles');

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
