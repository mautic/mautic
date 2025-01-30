<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\Factory\TransifexFactory;
use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\CoreBundle\Helper\UrlHelper;
use Mautic\Transifex\Connector\Resources;
use Mautic\Transifex\Exception\InvalidConfigurationException;
use Mautic\Transifex\Exception\ResponseException;
use Mautic\Transifex\Exception\TransifexException;
use Mautic\Transifex\Promise;
use Psr\Http\Message\ResponseInterface;
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
    public const NAME = 'mautic:transifex:push';

    public function __construct(
        private TransifexFactory $transifexFactory,
        private TranslatorInterface $translator,
        private LanguageHelper $languageHelper,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::NAME)
            ->addOption('bundle', null, InputOption::VALUE_OPTIONAL, 'Optional bundle to pull. Example value: WebhookBundle', null)
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command is used to push translation resources to Transifex

<info>php %command.full_name%</info>

You can optionally choose to update resources for one bundle only with the --bundle option:

<info>php %command.full_name% --bundle AssetBundle</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $bundleFilter = $input->getOption('bundle');
        $files        = $this->languageHelper->getLanguageFiles($bundleFilter ? [$bundleFilter] : []);

        try {
            $transifex = $this->transifexFactory->getTransifex();
        } catch (InvalidConfigurationException) {
            $output->writeln($this->translator->trans(
                'mautic.core.command.transifex_no_credentials')
            );

            return Command::FAILURE;
        }

        $resources = $transifex->getConnector(Resources::class);
        \assert($resources instanceof Resources);

        $existingResources = json_decode((string) $resources->getAll()->getBody(), true);
        $promises          = new \SplQueue();

        foreach ($files as $bundle => $stringFiles) {
            foreach ($stringFiles as $file) {
                $name    = $bundle.' '.str_replace('.ini', '', basename($file));
                $alias   = UrlHelper::stringURLUnicodeSlug($name);
                $content = file_get_contents($file);
                $output->writeln(
                    $this->translator->trans(
                        'mautic.core.command.transifex_processing_resource',
                        ['%resource%' => $name]
                    )
                );

                try {
                    if (false === $content) {
                        throw new \RuntimeException('Unable to read file '.$file);
                    }

                    if (!$resources->resourceExists($existingResources['data'], $alias)) {
                        $resources->create($name, $alias, 'INI');
                        $output->writeln(
                            $this->translator->trans('mautic.core.command.transifex_resource_created')
                        );
                    }

                    $promise = $transifex->getApiConnector()->createPromise(
                        $resources->uploadContent($alias, $content, true)
                    );
                    $promise->setFilePath($file);
                    $promises->enqueue($promise);
                } catch (TransifexException $exception) {
                    $output->writeln(
                        $this->translator->trans(
                            'mautic.core.command.transifex_error_pushing_data',
                            ['%message%' => $exception->getMessage()]
                        )
                    );
                }
            }
        }

        $transifex->getApiConnector()->fulfillPromises(
            $promises,
            function (ResponseInterface $response, Promise $promise) use ($output): void {
                $output->writeln(
                    $this->translator->trans(
                        'mautic.core.command.transifex_resource_updated',
                        ['%file%' => $promise->getFilePath()]
                    )
                );
            },
            function (ResponseException $exception, Promise $promise) use ($output): void {
                $output->writeln($promise->getFilePath());
                $output->writeln($exception->getMessage());
            }
        );

        return Command::SUCCESS;
    }

    protected static $defaultDescription = 'Pushes Mautic translation resources to Transifex';
}
