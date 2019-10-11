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

use DateTime;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Entity\Notification;
use Mautic\CoreBundle\Entity\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PurgeStaleNotificationsCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $inputOptions = [
            new InputOption(
                'stale-days',
                null,
                InputOption::VALUE_OPTIONAL,
                'Notifications from "X" days ago will be considered stale.',
                '-7 day'
            ),
            new InputOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Do a dry run without actually deleting anything.'
            ),
        ];

        $this->setName('mautic:notifications:purge')
            ->setDescription("Purge stale users' notfications.")
            ->setDefinition($inputOptions)
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command is used to purge stale user's notifications

<info>php %command.full_name%</info>

You can set the --stale-days flag to change the number of days to consider stale:

<info>php %command.full_name% --stale-days="-3 days"</info>
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options = $input->getOptions();
        $from    = new DateTime($options['stale-days']);

        /** @var EntityManager $em */
        $em = $this->getContainer()->get(EntityManager::class);
        /** @var NotificationRepository $repo */
        $repo = $em->getRepository(Notification::class);

        if ($options['dry-run']) {
            $qb = $repo->createQueryBuilder('n')
                    ->select('count(n.id)')
                    ->where('n.dateAdded <= :from')
                    ->setParameter('from', $from->format('Y-m-d H:i:s'));
            $count = $qb->getQuery()->getSingleScalarResult();

            $output->writeln("<info>{$count} notification(s) would be purged.</info> ", false);

            return 0;
        }

        $output->writeln("Purging notifications older than {$from->format('Y-m-d')}");
        $repo->deleteNotifications($from);
        $output->writeln('Finished.');

        return 0;
    }
}
