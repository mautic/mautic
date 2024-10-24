<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Command;

use Doctrine\DBAL\Exception as DBALException;
use Mautic\CoreBundle\Entity\AuditLogRepository;
use Mautic\CoreBundle\Entity\IpAddressRepository;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AnonymizeIpCommand extends Command
{
    protected static $defaultDescription = 'Delete all stored ip addresses.';
    /**
     * @var string
     */
    public const COMMAND_NAME = 'mautic:anonymize:ip';

    public function __construct(private IpAddressRepository $ipAddressRepository, private CoreParametersHelper $coreParametersHelper, private AuditLogRepository $auditLogRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->coreParametersHelper->get('anonymize_ip')) {
            return $this->exitWithError('Anonymization could not be done because anonymize Ip feature is disabled for this instance.', $output);
        }
        try {
            $anonymizedRows = $this->ipAddressRepository->anonymizeAllIpAddress();
            $anonymizedRows += $this->auditLogRepository->anonymizeAllIpAddress();
            $output->writeln(sprintf('<info>%s IP addresses have been anonymized</info>', $anonymizedRows));
        } catch (DBALException $e) {
            return $this->exitWithError(sprintf('Anonymization of IP addresses failed because of database error: %s', $e->getMessage()), $output);
        }

        return Command::SUCCESS;
    }

    private function exitWithError(string $message, OutputInterface $output): int
    {
        $output->writeln(sprintf('<error>%s</error>', $message));

        return 1;
    }
}
