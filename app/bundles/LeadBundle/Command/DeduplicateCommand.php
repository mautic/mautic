<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\LeadBundle\Deduplicate\ContactDeduper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\TranslatorInterface;

class DeduplicateCommand extends ModeratedCommand
{
    /**
     * @var ContactDeduper
     */
    private $contactDeduper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * DeduplicateCommand constructor.
     *
     * @param ContactDeduper      $contactDeduper
     * @param TranslatorInterface $translator
     */
    public function __construct(ContactDeduper $contactDeduper, TranslatorInterface $translator)
    {
        parent::__construct();

        $this->contactDeduper = $contactDeduper;
        $this->translator     = $translator;
    }

    public function configure()
    {
        parent::configure();

        $this->setName('mautic:contacts:deduplicate')
            ->setDescription('Merge contacts based on same unique identifiers')
            ->addOption(
                '--newer-into-older',
                null,
                InputOption::VALUE_NONE,
                'By default, this command will merge older contacts and activity into the newer. Use this flag to reverse that behavior.'
            )
            ->setHelp(
                <<<'EOT'
The <info>%command.name%</info> command will dedpulicate contacts based on unique identifier values. 

<info>php %command.full_name%</info>
EOT
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $newerIntoOlder = (bool) $input->getOption('newer-into-older');
        $count          = $this->contactDeduper->deduplicate($newerIntoOlder, $output);

        $output->writeln('');
        $output->writeln(
            $this->translator->trans(
                'mautic.lead.merge.count',
                [
                    '%count%' => $count,
                ]
            )
        );
    }
}
