<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\Helper\UpdateHelper;
use Mautic\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * CLI Command to fetch application updates
 */
class FindUpdatesCommand extends ContainerAwareCommand
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('mautic:update:find');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options = $input->getOptions();

        /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
        $translator = $this->getContainer()->get('translator');
        $translator->setLocale('en_US');

        $updateHelper = new UpdateHelper($this->getContainer()->get('mautic.factory'));
        $updateData   = $updateHelper->fetchData($this->getContainer()->getParameter('kernel.root_dir'), true);

        if ($updateData['error']) {
            $output->writeln('<error>' . $translator->trans($updateData['message']) . '</error>');
        } elseif ($updateData['message'] == 'mautic.core.updater.running.latest.version') {
            $output->writeln('<info>' . $translator->trans($updateData['message']) . '</info>');
        } elseif (isset($updateData['min_version'])) {
            $output->writeln($translator->trans($updateData['message'], array('%minVersion%' => $updateData['min_version'], '%phpVersion%' => $updateData['php_version'])));
        } else {
            $output->writeln($translator->trans($updateData['message'], array('%version%' => $updateData['version'], '%announcement%' => $updateData['announcement'])));
            $output->writeln($translator->trans('mautic.core.updater.cli.update'));
        }

        return 0;
    }
}
