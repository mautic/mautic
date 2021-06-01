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
use Mautic\CoreBundle\Helper\CoreParametersHelper;
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

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    public function __construct(IpAddressRepository $ipAddressRepository, CoreParametersHelper $coreParametersHelper)
    {
        $this->ipAddressRepository  = $ipAddressRepository;
        $this->coreParametersHelper = $coreParametersHelper;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Delete all stored ip addresses.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->coreParametersHelper->get('anonymize_ip')) {
            return $this->exitWithError('Anonymization could not be done because anonymize Ip feature is disabled for this instance.', $output);
        }
        try {
            $deletedRows = $this->ipAddressRepository->deleteAllIpAddress();
            $output->writeln(sprintf('<info>%s IP addresses have been deleted</info>', $deletedRows));
        } catch (DBALException $e) {
            return $this->exitWithError(sprintf('Deletion of IP addresses failed because of database error: %s', $e->getMessage()), $output);
        }

        return ExitCode::SUCCESS;
    }

    private function exitWithError(string $message, OutputInterface $output): int
    {
        $output->writeln(sprintf('<error>%s</error>', $message));

        return ExitCode::FAILURE;
    }
}
