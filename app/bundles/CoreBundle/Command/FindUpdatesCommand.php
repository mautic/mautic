<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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

        $updateHelper = $this->getContainer()->get('mautic.helper.update');
        $updateData   = $updateHelper->fetchData(true);

        if ($updateData['error']) {
            $output->writeln('<error>'.$translator->trans($updateData['message']).'</error>');
        } elseif ($updateData['message'] == 'mautic.core.updater.running.latest.version') {
            $output->writeln('<info>'.$translator->trans($updateData['message']).'</info>');
        } else {
            $output->writeln($translator->trans($updateData['message'], ['%version%' => $updateData['version'], '%announcement%' => $updateData['announcement']]));
            $output->writeln($translator->trans('mautic.core.updater.cli.update'));
        }

        return 0;
    }
}
