<?php

namespace Mautic\CoreBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command to fetch application updates.
 */
class FindUpdatesCommand extends \Symfony\Component\Console\Command\Command
{
    private \Symfony\Component\Translation\DataCollectorTranslator $dataCollectorTranslator;
    private \Mautic\CoreBundle\Factory\MauticFactory $mauticFactory;
    private \Mautic\CoreBundle\Helper\UpdateHelper $updateHelper;

    public function __construct(\Symfony\Component\Translation\DataCollectorTranslator $dataCollectorTranslator, \Mautic\CoreBundle\Factory\MauticFactory $mauticFactory, \Mautic\CoreBundle\Helper\UpdateHelper $updateHelper)
    {
        $this->dataCollectorTranslator = $dataCollectorTranslator;
        parent::__construct();
        $this->mauticFactory = $mauticFactory;
        $this->updateHelper  = $updateHelper;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
        $translator = $this->dataCollectorTranslator;
        $translator->setLocale($this->mauticFactory->getParameter('locale'));

        $updateHelper = $this->updateHelper;
        $updateData   = $updateHelper->fetchData(true);

        if ($updateData['error']) {
            $output->writeln('<error>'.$translator->trans($updateData['message']).'</error>');
        } elseif ('mautic.core.updater.running.latest.version' == $updateData['message']) {
            $output->writeln('<info>'.$translator->trans($updateData['message']).'</info>');
        } else {
            $output->writeln($translator->trans($updateData['message'], ['%version%' => $updateData['version'], '%announcement%' => $updateData['announcement']]));
            $output->writeln($translator->trans('mautic.core.updater.cli.update'));
        }

        return 0;
    }
}
