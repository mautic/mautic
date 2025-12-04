<?php

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\IpLookup\AbstractLocalDataLookup;
use Mautic\CoreBundle\IpLookup\AbstractLookup;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CLI Command to fetch updated Maxmind database.
 */
#[AsCommand(name: 'mautic:iplookup:download', description: 'Fetch remote datastores for IP lookup services that leverage local lookups', help: <<<'TXT'
                The <info>%command.name%</info> command is used to update local IP lookup data if applicable.

<info>php %command.full_name%</info>
TXT)]
class UpdateIpDataStoreCommand
{
    public function __construct(
        private TranslatorInterface $translator,
        private AbstractLookup $ipService,
    ) {
    }

    public function __invoke(OutputInterface $output): int
    {
        if ($this->ipService instanceof AbstractLocalDataLookup) {
            if ($this->ipService->downloadRemoteDataStore()) {
                $output->writeln('<info>'.$this->translator->trans('mautic.core.success').'</info>');
            } else {
                $remoteUrl = $this->ipService->getRemoteDateStoreDownloadUrl();
                $localPath = $this->ipService->getLocalDataStoreFilepath();

                if ($remoteUrl && $localPath) {
                    $output->writeln('<error>'.$this->translator->trans(
                        'mautic.core.ip_lookup.remote_fetch_error',
                        [
                            '%remoteUrl%' => $remoteUrl,
                            '%localPath%' => $localPath,
                        ]
                    ).'</error>');
                } else {
                    $output->writeln('<error>'.$this->translator->trans(
                        'mautic.core.ip_lookup.remote_fetch_error_generic'
                    ).'</error>');
                }
            }
        }

        return Command::SUCCESS;
    }
}
