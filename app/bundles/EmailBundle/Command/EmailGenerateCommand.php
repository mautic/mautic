<?php

/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\EmailBundle\Command;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\QueueEmailEvent;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Mautic\CoreBundle\Command\ModeratedCommand;

/**
 * CLI command to generate Segment Email
 */
class EmailGenerateCommand extends ModeratedCommand
{

    /**
     *
     * {@inheritdoc}
     *
     */
    protected function configure()
    {
        $this->setName('mautic:email:generate')
            ->setDescription('Generate Segment Email')
            ->addOption('--id', '-i', InputOption::VALUE_OPTIONAL, 'Email ID')
            ->addOption('--force', '-f', InputOption::VALUE_NONE, 'Force execution even if another process is assumed running.')

        // The <info>%command.name%</info> command is used to process the application's e-mail queue

        // <info>php %command.full_name%</info>
        // EOT
        // )
        ;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options = $input->getOptions();
        $objectId = $options['id'];
        $factory = $this->getContainer()->get('mautic.factory');
        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model = $factory->getModel('email');
        $email = $model->getEntity($objectId);

        if (is_null($email) || $email->getEmailType() == 'template') {
            // return $this->accessDenied();
            return 0;
        }

        if (!$this->checkRunStatus($input, $output, ($objectId) ? $objectId : 'all')) {
            return 0;
        }
        // make sure email and category are published
        $category = $email->getCategory();
        $catPublished = (!empty($category)) ? $category->isPublished() : true;
        $published = $email->isPublished();

        if (!$catPublished || !$published) {
            return 0;
        }

        $pending = $model->getPendingLeads($email, null, true);
        $output->writeln('<info>There is ' . $pending . ' mails to generate</info>');
        $sendStat = $model->sendEmailToLists($email);
        if ($pending > 0) {
            $output->writeln('<info>' . $sendStat[0] . ' mails sent</info>');
            $output->writeln('<info>' . $sendStat[1] . ' mails failed</info>');
        }

        $this->completeRun();

        return 0;
    }
}
