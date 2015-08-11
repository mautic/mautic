<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Helper\ImapHelper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command to check for messages
 */
class ProcessFetchEmailCommand extends ContainerAwareCommand
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mautic:fetch:email')
            ->setAliases(array(
                'mautic:process:bounces',
                'mautic:fetch:bounces',
                'mautic:check:email'
            ))
            ->setDescription('Fetch and process monitored email.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command is used to fetch and process bounce messages. Configure the bounce IMAP command in Mautic's Configuration.

<info>php %command.full_name%</info>
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container  = $this->getContainer();
        $factory    = $container->get('mautic.factory');
        $dispatcher = $container->get('event_dispatcher');

        // Check to see if a monitored server is configured
        $bounceHost = $factory->getParameter('monitored_email_host');
        if (empty($bounceHost)) {
            $output->writeln('Configure an account in Mautic\'s Configuration');

            return 0;
        }

        /** @var ImapHelper $imapHelper */
        $imapHelper = $factory->getHelper('imap');

        // Check connection
        try {
            $imapHelper->connect();
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());

            return 0;
        }

        // Get mail
        $mailIds = $imapHelper->fetchNew();

        if (count($mailIds)) {
            foreach ($mailIds as $id) {
                $mail = $imapHelper->mailbox->getMail($id);

                if ($dispatcher->hasListeners(CoreEvents::EMAIL_PROCESSED)) {
                    $event = new EmailEvent($mail);
                    $dispatcher->dispatch(CoreEvents::EMAIL_PROCESSED, $event);
                }
            }
        }

        return 0;
    }
}
