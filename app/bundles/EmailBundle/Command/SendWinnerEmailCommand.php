<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Command;

use Mautic\CoreBundle\Entity\VariantEntityInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sends email to winner variant after predetermined amount of time.
 */
class SendWinnerEmailCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mautic:email:sendwinner')
            ->setDescription('Send winner email variant to remaining contacts')
            ->addOption('--id', null, InputOption::VALUE_REQUIRED, 'Parent variant email id.')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command is used to send winner email variant to remaining contacts after predetermined amount of timeá

<info>php %command.full_name%</info>
EOT
        );

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $model     = $container->get('mautic.email.model.email');
        $emailId   = $input->getOption('id');

        $email = $model->getEntity($emailId);

        //g et A/B test information
        list($parent, $children) = $email->getVariants();

        $abTestSettings = $container->get('mautic.core.variant.abtest_settings')->getAbTestSettings($parent);

        if (!array_key_exists('sendWinnerWait', $abTestSettings) || $abTestSettings['sendWinnerWait'] < 1) {
            $output->writeln('Amount of time to send winner email not specified in AB test variant settings.');

            return 1;
        }

        if (!$this->isReady($parent->getId(), $abTestSettings['sendWinnerWait'])) {
            // too early
            $output->writeln('Predetermined amount of time haven\'t passed yet');

            return 1;
        }

        if (count($children) > 0) {
            $winner = $this->getWinner($output, $parent, $abTestSettings['winnerCriteria']);
        } else {
            // no variants
            $output->writeln('Email doesn\'t have variants');

            return 1;
        }

        if (empty($winner)) {
            // no winners
            $output->writeln('No winner yet or email has been sent already.');

            return 1;
        }

        $model->convertWinnerVariant($winner);

        // send winner email

        $output->writeln('Winner email ['.$winner->getId().'] has been sent to remaining contacts.');

        return 0;
    }

    /**
     * @param OutputInterface $output
     * @param VariantEntityInterface $parentVariant
     * @param string $winnerCriteria
     *
     * @return |null
     */
    private function getWinner(OutputInterface $output, VariantEntityInterface $parentVariant, $winnerCriteria)
    {
        $container  = $this->getContainer();
        $model      = $container->get('mautic.email.model.email');

        $criteria               = $model->getBuilderComponents($parentVariant, 'abTestWinnerCriteria');
        $abTestResultService    = $container->get('mautic.core.variant.abtest_result');
        $abTestResults          = $abTestResultService->getAbTestResult($parentVariant, $criteria['criteria'][$winnerCriteria]);
        $winners                = $abTestResults['winners'];

        if (empty($winners)) {
            return null;
        }

        $output->writeln('Winner ids: '.implode($winners, ','));

        $winner = $model->getEntity($winners[0]);

        $model->sendEmailToLists($winner);

        return $winner;
    }

    /**
     * @param int $emailId
     * @param int $waitHours
     *
     * @return bool
     *
     * @throws \Exception
     */
    private function isReady($emailId, $waitHours)
    {
        $container  = $this->getContainer();
        $repo       = $container->get('mautic.email.repository.stat');

        $lastSentDate   = $repo->getEmailSentLastDate($emailId);
        $sendWinnerTime = new \DateTime($lastSentDate);
        $sendWinnerTime->modify("+{$waitHours} hours");

        $now = new \DateTime("now");

        if($now > $sendWinnerTime) {
            return true;
        }

        return false;
    }
}
