<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Command;

use Mautic\CoreBundle\Helper\ExitCode;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteLeadListsCommand extends Command
{
    public const COMMAND_NAME = 'mautic:segment:delete';

    public function __construct(
        private ListModel $listModel,
        private LeadListRepository $leadListRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Hard-delete segment(s) and all its references.')
            ->addArgument(
                'list-id',
                InputArgument::OPTIONAL,
                'Segment id to delete.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $listId   = (int) $input->getArgument('list-id');

        // single entity
        if (!empty($listId)) {
            if ($list = $this->listModel->getSoftDeletedEntity($listId)) {
                $this->deleteLeadList($list);
                $output->writeln("<info>segment {$listId} has been deleted.</info>");

                return ExitCode::SUCCESS;
            } else {
                $output->writeln("<info>segment {$listId} couldn't be deleted as it was not found.</info>");

                return ExitCode::FAILURE;
            }
        } else {
            // All soft deleted segments
            $prefix = $this->leadListRepository->getTableAlias();

            /** @var \Doctrine\ORM\Internal\Hydration\IterableResult $leadLists */
            $leadLists = $this->leadListRepository->getEntities([
                'filter' => [
                    'force' => [
                        [
                            'column' => $prefix.'.deleted',
                            'expr'   => 'isNotNull',
                        ],
                    ],
                ],
                'ignore_paginator'  => true,
                'iterable_mode'     => true,
            ]);
            foreach ($leadLists as $list) {
                $listId = $list->getId();
                $this->deleteLeadList($list);
                $output->writeln("<info>segment {$listId} has been deleted.</info>");
            }

            return ExitCode::SUCCESS;
        }
    }

    private function deleteLeadList(LeadList $list): void
    {
        $this->listModel->removeLeadsByListId($list->getId());
        $this->listModel->hardDeleteEntity($list);
    }
}
