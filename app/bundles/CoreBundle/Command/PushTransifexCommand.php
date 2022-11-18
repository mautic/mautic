<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\Factory\TransifexFactory;
use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\CoreBundle\Helper\UrlHelper;
use Mautic\Transifex\Connector\Resources;
use Mautic\Transifex\Exception\InvalidConfigurationException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CLI Command to push language resources to Transifex.
 */
class PushTransifexCommand extends Command
{
    private TransifexFactory $transifexFactory;
    private TranslatorInterface $translator;
    private LanguageHelper $languageHelper;

    public function __construct(
        TransifexFactory $transifexFactory,
        TranslatorInterface $translator,
        LanguageHelper $languageHelper
    ) {
        $this->transifexFactory = $transifexFactory;
        $this->translator       = $translator;
        $this->languageHelper   = $languageHelper;

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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $options = $input->getOptions();
        $create  = $options['create'];
        $files   = $this->languageHelper->getLanguageFiles();

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
}
