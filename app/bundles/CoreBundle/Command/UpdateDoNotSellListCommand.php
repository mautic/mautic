<?php

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\Helper\MaxMindDoNotSellDownloadHelper;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateDoNotSellListCommand extends Command
{
    private $maxMindDoNotSellDownloadHelper;
    private $translator;

    public function __construct(MaxMindDoNotSellDownloadHelper $maxMindDoNotSellDownloadHelper, Translator $translator)
    {
        parent::__construct();
        $this->maxMindDoNotSellDownloadHelper = $maxMindDoNotSellDownloadHelper;
        $this->translator                     = $translator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('mautic:donotsell:download')
            ->setDescription('Fetch remote do not sell list from MaxMind')
            ->setHelp(
                <<<'EOT'
                The <info>%command.name%</info> command is used to update MaxMind Do Not Sell list.

<info>php %command.full_name%</info>
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->maxMindDoNotSellDownloadHelper->downloadRemoteDataStore()) {
            $output->writeln('<info>'.$this->translator->trans('mautic.core.success').'</info>');
        } else {
            $remoteUrl = $this->maxMindDoNotSellDownloadHelper->getRemoteDateStoreDownloadUrl();
            $localPath = $this->maxMindDoNotSellDownloadHelper->getLocalDataStoreFilepath();

            if ($remoteUrl && $localPath) {
                //TODO New messages
                $output->writeln('<error>'.$this->translator->trans(
                        'mautic.core.ip_lookup.remote_fetch_error',
                        [
                            '%remoteUrl%' => $remoteUrl,
                            '%localPath%' => $localPath,
                        ]
                    ).'</error>');
            } else {
                //TODO New messages
                $output->writeln('<error>'.$this->translator->trans(
                        'mautic.core.ip_lookup.remote_fetch_error_generic'
                    ).'</error>');
            }
        }

        return 0;
    }
}
