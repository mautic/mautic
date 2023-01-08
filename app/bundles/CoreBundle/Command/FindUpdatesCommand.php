<?php

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\Helper\UpdateHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CLI Command to fetch application updates.
 */
class FindUpdatesCommand extends Command
{
    private TranslatorInterface $translator;
    private UpdateHelper $updateHelper;

    public function __construct(TranslatorInterface $translator, UpdateHelper $updateHelper)
    {
        parent::__construct();

        $this->translator   = $translator;
        $this->updateHelper = $updateHelper;
    }

    protected function configure()
    {
        $this->setName('mautic:update:find')
            ->setDescription('Fetches updates for Mautic')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command checks for updates for the Mautic application.

<info>php %command.full_name%</info>
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $updateData = $this->updateHelper->fetchData(true);

        if ($updateData['error']) {
            $output->writeln('<error>'.$this->translator->trans($updateData['message']).'</error>');
        } elseif ('mautic.core.updater.running.latest.version' == $updateData['message']) {
            $output->writeln('<info>'.$this->translator->trans($updateData['message']).'</info>');
        } else {
            $output->writeln($this->translator->trans($updateData['message'], ['%version%' => $updateData['version'], '%announcement%' => $updateData['announcement']]));
            $output->writeln($this->translator->trans('mautic.core.updater.cli.update'));
        }

        return 0;
    }
}
