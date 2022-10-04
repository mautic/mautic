<?php

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\IpLookup\AbstractLocalDataLookup;
use Mautic\CoreBundle\IpLookup\AbstractLookup;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CLI Command to fetch updated Maxmind database.
 */
class UpdateIpDataStoreCommand extends Command
{
    private TranslatorInterface $translator;
    private AbstractLookup $ipService;

    public function __construct(
        TranslatorInterface $translator,
        AbstractLookup $ipService
    ) {
        $this->translator = $translator;
        $this->ipService  = $ipService;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('mautic:iplookup:download')
            ->setDescription('Fetch remote datastores for IP lookup services that leverage local lookups')
            ->setHelp(
                <<<'EOT'
                The <info>%command.name%</info> command is used to update local IP lookup data if applicable.

<info>php %command.full_name%</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
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

        return 0;
    }
}
