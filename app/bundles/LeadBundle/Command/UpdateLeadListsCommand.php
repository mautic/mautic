<?php

namespace Mautic\LeadBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UpdateLeadListsCommand extends ModeratedCommand
{
    public const NAME = 'mautic:segments:update';

    public function __construct(
        private ListModel $listModel,
        private TranslatorInterface $translator,
        PathsHelper $pathsHelper,
        CoreParametersHelper $coreParametersHelper
    ) {
        parent::__construct($pathsHelper, $coreParametersHelper);
    }

    protected function configure()
    {
        $this
            ->setName('mautic:segments:update')
            ->setAliases(['mautic:segments:rebuild'])
            ->addOption(
                '--batch-limit',
                '-b',
                InputOption::VALUE_OPTIONAL,
                'Set batch size of contacts to process per round. Defaults to 300.',
                300
            )
            ->addOption(
                '--max-contacts',
                '-m',
                InputOption::VALUE_OPTIONAL,
                'Set max number of contacts to process per segment for this script execution. Defaults to all.',
                false
            )
            ->addOption(
                '--list-id',
                '-i',
                InputOption::VALUE_OPTIONAL,
                'Specific ID to rebuild. Defaults to all.',
                false
            )
            ->addOption(
                '--timing',
                '-tm',
                InputOption::VALUE_OPTIONAL,
                'Measure timing of build with output to CLI .',
                false
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id                    = $input->getOption('list-id');
        $batch                 = $input->getOption('batch-limit');
        $max                   = $input->getOption('max-contacts');
        $enableTimeMeasurement = (bool) $input->getOption('timing');
        $output                = ($input->getOption('quiet')) ? new NullOutput() : $output;

        if (!$this->checkRunStatus($input, $output, $id)) {
            return \Symfony\Component\Console\Command\Command::SUCCESS;
        }

        if ($enableTimeMeasurement) {
            $startTime = microtime(true);
        }

        if ($id) {
            $list = $this->listModel->getEntity($id);

            if (!$list) {
                $output->writeln('<error>'.$this->translator->trans('mautic.lead.list.rebuild.not_found', ['%id%' => $id]).'</error>');

                return \Symfony\Component\Console\Command\Command::FAILURE;
            }

            $this->rebuildSegment($list, $batch, $max, $output);
        } else {
            $leadLists = $this->listModel->getEntities(
                [
                    'iterator_mode' => true,
                ]
            );

            while (false !== ($leadList = $leadLists->next())) {
                // Get first item; using reset as the key will be the ID and not 0
                /** @var LeadList $leadList */
                $leadList                  = reset($leadList);
                $startTimeForSingleSegment = time();
                $this->rebuildSegment($leadList, $batch, $max, $output);
                if ($enableTimeMeasurement) {
                    $totalTime = round(microtime(true) - $startTimeForSingleSegment, 2);
                    $output->writeln('<fg=cyan>'.$this->translator->trans('mautic.lead.list.rebuild.contacts.time', ['%time%' => $totalTime]).'</>'."\n");
                }
                unset($leadList);
            }
            unset($leadLists);
        }

        $this->completeRun();

        if ($enableTimeMeasurement) {
            $totalTime = round(microtime(true) - $startTime, 2);
            $output->writeln('<fg=magenta>'.$this->translator->trans('mautic.lead.list.rebuild.total.time', ['%time%' => $totalTime]).'</>'."\n");
        }

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

    private function rebuildSegment(LeadList $segment, int $batch, int $max, OutputInterface $output): void
    {
        if ($segment->isPublished()) {
            $output->writeln('<info>'.$this->translator->trans('mautic.lead.list.rebuild.rebuilding', ['%id%' => $segment->getId()]).'</info>');
            $startTime   = microtime(true);
            $processed   = $this->listModel->rebuildListLeads($segment, $batch, $max, $output);
            $rebuildTime = round(microtime(true) - $startTime, 2);
            if (0 >= (int) $max) {
                // Only full segment rebuilds count
                $segment->setLastBuiltDateToCurrentDatetime();
                $segment->setLastBuiltTime($rebuildTime);
                $this->listModel->saveEntity($segment);
            }

            $output->writeln(
                '<comment>'.$this->translator->trans('mautic.lead.list.rebuild.leads_affected', ['%leads%' => $processed]).'</comment>'
            );
        }
    }

    protected static $defaultDescription = 'Update contacts in smart segments based on new contact data.';
}
