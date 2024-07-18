<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Service;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Symfony\Component\Security\Core\User\UserInterface;

class ExportLogger
{
    public const LEAD_EXPORT   = 'lead.export';
    public const REPORT_EXPORT = 'report.export';

    protected Logger $logger;

    protected mixed $logPath;

    protected mixed $logFileName;

    protected mixed $maxFiles;

    /**
     * @throws \Exception
     */
    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->logPath     = $coreParametersHelper->get('log_exports_path');
        $this->logFileName = $coreParametersHelper->get('log_file_exports_name');
        $this->maxFiles    = $coreParametersHelper->get('max_log_exports_files');
        $this->logger      = new Logger($this->getLoggerName());
        $this->registerHandlers();
    }

    public function getLoggerName(): string
    {
        return 'logger_exports';
    }

    public function getFileName(): string
    {
        return $this->logFileName ?? 'exports_prod.php';
    }

    public function getLogPath(): string
    {
        return $this->logPath ?? '%kernel.root_dir%/../var/logs/exports';
    }

    public function getMaxFiles(): int
    {
        return $this->maxFiles ?? 7;
    }

    /**
     * @param array<string|int, array<string, mixed>|int|string> $args
     */
    public function loggerInfo(?UserInterface $user, string $type, array $args): void
    {
        $msg = 'User #'.$user->getUsername().'_'.$type.' exported with params: ';
        $this->logger->info($msg, $args);
    }

    /**
     * Register logger handlers.
     *
     * @throws \Exception
     */
    private function registerHandlers(): void
    {
        $this->logger->pushHandler(new RotatingFileHandler(
            $this->getLogPath().'/'.$this->getFileName(),
            $this->getMaxFiles(),
            Logger::INFO
        ));
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }
}
