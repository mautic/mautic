<?php

namespace Mautic\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command to fetch application updates.
 */
class FindUpdatesCommand extends ContainerAwareCommand
{
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

Return codes:
0 - An update is available for Mautic.
1 - Running latest version of Mautic.
2 - An error occurred while fetching available updates of Mautic.
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
        $translator = $this->getContainer()->get('translator');
        $translator->setLocale($this->getContainer()->get('mautic.factory')->getParameter('locale'));

        /** @var \Mautic\CoreBundle\Helper\UpdateHelper $updateHelper */
        $updateHelper = $this->getContainer()->get('mautic.helper.update');
        $updateData   = $updateHelper->fetchData(true);

        if (true === $updateData['error']) {
            $output->writeln('<error>'.$translator->trans($updateData['message']).'</error>');

            return 2;
        }

        if ('mautic.core.updater.running.latest.version' === $updateData['message']) {
            $output->writeln('<info>'.$translator->trans($updateData['message']).'</info>');

            return 1;
        }

        $output->writeln($translator->trans($updateData['message'], ['%version%' => $updateData['version'], '%announcement%' => $updateData['announcement']]));
        $output->writeln($translator->trans('mautic.core.updater.cli.update'));

        return 0;
    }
}
