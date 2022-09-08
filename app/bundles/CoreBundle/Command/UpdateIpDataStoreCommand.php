<?php

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\IpLookup\AbstractLocalDataLookup;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command to fetch updated Maxmind database.
 */
class UpdateIpDataStoreCommand extends \Symfony\Component\Console\Command\Command
{
    private \Mautic\CoreBundle\IpLookup\AbstractLookup $lookup;
    private \Mautic\CoreBundle\Factory\MauticFactory $mauticFactory;

    public function __construct(\Mautic\CoreBundle\IpLookup\AbstractLookup $lookup, \Mautic\CoreBundle\Factory\MauticFactory $mauticFactory)
    {
        $this->lookup = $lookup;
        parent::__construct();
        $this->mauticFactory = $mauticFactory;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ipService  = $this->lookup;
        $factory    = $this->mauticFactory;
        $translator = $factory->getTranslator();

        if ($ipService instanceof AbstractLocalDataLookup) {
            if ($ipService->downloadRemoteDataStore()) {
                $output->writeln('<info>'.$translator->trans('mautic.core.success').'</info>');
            } else {
                $remoteUrl = $ipService->getRemoteDateStoreDownloadUrl();
                $localPath = $ipService->getLocalDataStoreFilepath();

                if ($remoteUrl && $localPath) {
                    $output->writeln('<error>'.$translator->trans(
                        'mautic.core.ip_lookup.remote_fetch_error',
                        [
                            '%remoteUrl%' => $remoteUrl,
                            '%localPath%' => $localPath,
                        ]
                    ).'</error>');
                } else {
                    $output->writeln('<error>'.$translator->trans(
                        'mautic.core.ip_lookup.remote_fetch_error_generic'
                    ).'</error>');
                }
            }
        }

        return 0;
    }
}
