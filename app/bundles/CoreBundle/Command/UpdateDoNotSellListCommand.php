<?php

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\Helper\MaxMindDoNotSellDownloadHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'mautic:donotsell:download',
    description: 'Fetch remote do not sell list from MaxMind'
)]
class UpdateDoNotSellListCommand extends Command
{
    public function __construct(
        private MaxMindDoNotSellDownloadHelper $maxMindDoNotSellDownloadHelper,
        private TranslatorInterface $translator,
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setHelp(
                <<<'EOT'
                The <info>%command.name%</info> command is used to update MaxMind Do Not Sell list.

<info>php %command.full_name%</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->maxMindDoNotSellDownloadHelper->downloadRemoteDataStore()) {
            $output->writeln('<info>'.$this->translator->trans('mautic.core.success').'</info>');
        } else {
            $remoteUrl = $this->maxMindDoNotSellDownloadHelper->getRemoteDataStoreDownloadUrl();
            $localPath = $this->maxMindDoNotSellDownloadHelper->getLocalDataStoreFilepath();

            if ($remoteUrl && $localPath) {
                $output->writeln('<error>'.$this->translator->trans(
                    'mautic.core.do_not_sell.remote_fetch_error',
                    [
                        '%remoteUrl%' => $remoteUrl,
                        '%localPath%' => $localPath,
                    ]
                ).'</error>');
            } else {
                $output->writeln('<error>'.$this->translator->trans(
                    'mautic.core.do_not_sell.remote_fetch_error_generic'
                ).'</error>');
            }
        }

        return Command::SUCCESS;
    }
}
