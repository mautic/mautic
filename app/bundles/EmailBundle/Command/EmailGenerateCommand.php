<?php

/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\EmailBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Factory\MauticFactory;

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
            ->setDescription('Generate Segment or Feed Email')
            ->addOption('--id', '-id', InputOption::VALUE_REQUIRED, 'Email ID')
            ->addOption('--force', '-f', InputOption::VALUE_NONE, 'Force execution even if another process is assumed running.');
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
        /** @var MauticFactory $factory */
        $factory = $this->getContainer()->get('mautic.factory');
        $logger = $factory->getLogger();
        $translator = $factory->getTranslator();
        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model = $factory->getModel('email');
        $email = $model->getEntity($objectId);

        if (is_null($email) || $email->getEmailType() == 'template') {
            // this command can be use only for list and feed mail
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
        try {
            $pending = $model->getPendingLeads($email, null, true);
            $output->writeln('<info>' . $translator->trans('mautic.email.generate.pending', array(
                '%pending%' => $pending
            )) . '</info>');
            $sendStat = $model->sendEmailToLists($email);
            if ($pending > 0) {
                $output->writeln('<info>' . $translator->trans('mautic.email.stat.sentcount', array(
                    '%count%' => $sendStat[0]
                )) . '</info>');
                $output->writeln('<info>' . $translator->trans('mautic.email.stat.failcount', array(
                    '%count%' => $sendStat[1]
                )) . '</info>');
            }

            $this->completeRun();
            return 0;
        } catch (\Exception $e) {
            $output->writeln('<info>'.$e->getMessage().'</info>');
            $logger->addError($e->getMessage());
            $this->completeRun();
            return -1;
        }
    }
}
