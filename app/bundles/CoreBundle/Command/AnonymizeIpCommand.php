<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic. All rights reserved
 * @author      Mautic
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Command;

use Doctrine\DBAL\DBALException;
use Mautic\CoreBundle\Entity\IpAddressRepository;
use Mautic\CoreBundle\Helper\ExitCode;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AnonymizeIpCommand extends Command
{
    /**
     * @var string
     */
    public const COMMAND_NAME = 'mautic:anonymize:ip';

    /**
     * @var IpAddressRepository
     */
    private $ipAddressRepository;

    public function __construct(IpAddressRepository $ipAddressRepository)
    {
        $this->ipAddressRepository = $ipAddressRepository;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Delete all stored ip addresses.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $deletedRows = $this->ipAddressRepository->deleteAllIpAddress();
            $output->writeln(sprintf('<info>%s IP addresses have been deleted</info>', $deletedRows));
        } catch (DBALException $e) {
            $output->writeln(sprintf('<error>Deletion of IP addresses failed because of database error: %s</error>', $e->getMessage()));

            return ExitCode::FAILURE;
        }

        return ExitCode::SUCCESS;
    }
}
