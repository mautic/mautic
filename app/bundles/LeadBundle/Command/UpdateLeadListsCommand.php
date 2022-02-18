<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Segment\Query\QueryException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateLeadListsCommand extends ModeratedCommand
{
    public const NAME = 'mautic:segments:update';

    protected function configure()
    {
        $this
            ->setName('mautic:segments:update')
            ->setAliases(['mautic:segments:rebuild'])
            ->setDescription('Update contacts in smart segments based on new contact data.')
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container  = $this->getContainer();
        $translator = $container->get('translator');

        /** @var \Mautic\LeadBundle\Model\ListModel $listModel */
        $listModel = $container->get('mautic.lead.model.list');

        $id                    = $input->getOption('list-id');
        $batch                 = (int) $input->getOption('batch-limit');
        $max                   = $input->getOption('max-contacts');
        $enableTimeMeasurement = (bool) $input->getOption('timing');
        $output                = ($input->getOption('quiet')) ? new NullOutput() : $output;

        if (!$this->checkRunStatus($input, $output, $id)) {
            return 0;
        }

        if ($enableTimeMeasurement) {
            $startTime = microtime(true);
        }

        if ($id) {
            $list = $listModel->getEntity($id);

            if (null !== $list) {
                if ($list->isPublished()) {
                    $output->writeln('<info>'.$translator->trans('mautic.lead.list.rebuild.rebuilding', ['%id%' => $id]).'</info>');
                    $processed = 0;
                    try {
                        $processed = $listModel->rebuildListLeads($list, $batch, $max, $output);
                        if (0 >= (int) $max) {
                            // Only full segment rebuilds count
                            $list->setLastBuiltDateToCurrentDatetime();
                            $listModel->saveEntity($list);
                        }
                    } catch (QueryException $e) {
                        $this->getContainer()->get('monolog.logger.mautic')->error('Query Builder Exception: '.$e->getMessage());
                    }

                    $output->writeln(
                        '<comment>'.$translator->trans('mautic.lead.list.rebuild.leads_affected', ['%leads%' => $processed]).'</comment>'
                    );
                }
            } else {
                $output->writeln('<error>'.$translator->trans('mautic.lead.list.rebuild.not_found', ['%id%' => $id]).'</error>');
            }
        } else {
            $segments = $listModel->getEntities(
                [
                    'iterator_mode' => true,
                ]
            );

            $segments = $this->getPrioritizedSegments($segments);

            foreach ($segments as $segment) {
                $output->writeln(
                    '<info>'.$translator->trans(
                        'mautic.lead.list.rebuild.rebuilding',
                        ['%id%' => $segment->getId()]
                    ).'</info>'
                );

                $startTimeForSingleSegment = microtime(true);
                $processed                 = $listModel->rebuildListLeads($segment, $batch, $max, $output);

                if (0 >= (int) $max) {
                    // Only full segment rebuilds count
                    $segment->setLastBuiltDateToCurrentDatetime();
                    $listModel->saveEntity($segment);
                }

                $output->writeln(
                        '<comment>'.$translator->trans('mautic.lead.list.rebuild.leads_affected', ['%leads%' => $processed]).'</comment>'
                    );
                if ($enableTimeMeasurement) {
                    $totalTime = round(microtime(true) - $startTimeForSingleSegment, 3);
                    $output->writeln($translator->trans('mautic.lead.list.rebuild.total.time', ['%time%' => $totalTime])."\n");
                }
            }
        }
        $this->completeRun();

        if ($enableTimeMeasurement) {
            $totalTime = round(microtime(true) - $startTime, 3);
            $output->writeln($translator->trans('mautic.lead.list.rebuild.total.time', ['%time%' => $totalTime]));
        }

        return 0;
    }

    /**
     * Build based on filters. Segment membership filters process on the end.
     *
     * @param \Doctrine\ORM\Internal\Hydration\IterableResult|array<mixed> $segments
     *
     * @return LeadList[]
     */
    protected function getPrioritizedSegments($segments): array
    {
        $simpleSegments  = [];
        $complexSegments = [];
        while (false !== ($segment = $segments->next())) {
            // Get first item; using reset as the key will be the ID and not 0
            /** @var LeadList $segment */
            $segment = reset($segment);
            if ($segment->isPublished()) {
                if (!$this->hasComplexFilter($segment->getFilters())) {
                    $simpleSegments[] = $segment;
                } else {
                    $complexSegments[] = $segment;
                }
            }
            unset($segment);
        }

        return array_merge($simpleSegments, $complexSegments);
    }

    /**
     * @param array<array<int|string>> $filters
     */
    protected function hasComplexFilter(array $filters): bool
    {
        foreach ($filters as $filter) {
            if ('leadlist' == $filter['field']) {
                return true;
            }
        }

        return false;
    }
}
