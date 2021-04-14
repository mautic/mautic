<?php

namespace Mautic\CoreBundle\Service;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\UserBundle\Entity\User;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

class ExportLogger
{
    const LEAD_EXPORT   = 'lead.exports';
    const REPORT_EXPORT = 'report.exports';

    protected $logger;

    protected $logPath;

    protected $logFileName;

    protected $maxFiles;

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

    /**
     * @return string
     */
    public function getLoggerName()
    {
        return 'logger_exports';
    }

    /**
     * @return array|false|mixed|string
     */
    public function getFileName()
    {
        return $this->logFileName ?? 'exports_prod.php';
    }

    /**
     * @return array|false|mixed|string
     */
    public function getLogPath()
    {
        return $this->logPath ?? '%kernel.root_dir%/../var/logs/exports';
    }

    /**
     * @return array|false|int|mixed|string
     */
    public function getMaxFiles()
    {
        return $this->maxFiles ?? 7;
    }

    public function loggerInfo(User $user, string $type, array $args)
    {
        $msg = 'User #'.$user->getId().'_'.crc32($user->getEmail()).' '.$type.' exported with params: ';
        $this->logger->info($msg, $args);
    }

    /**
     * Register logger handlers.
     *
     * @throws \Exception
     */
    private function registerHandlers()
    {
        $this->logger->pushHandler(new RotatingFileHandler(
            $this->getLogPath().'/'.$this->getFileName(),
            $this->getMaxFiles(),
            Logger::INFO
        ));
    }
}
