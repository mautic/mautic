<?php

namespace Mautic\CoreBundle\Monolog\Handler;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

class FileLogHandler extends RotatingFileHandler
{
    public function __construct(CoreParametersHelper $coreParametersHelper, FormatterInterface $exceptionFormatter)
    {
        $logPath     = $coreParametersHelper->get('log_path');
        $logFileName = $coreParametersHelper->get('log_file_name');
        $maxFiles    = $coreParametersHelper->get('max_log_files');
        $debugMode   = $coreParametersHelper->get('debug', false) || (defined('MAUTIC_ENV') && 'dev' === MAUTIC_ENV);
        $level       = $debugMode ? Logger::DEBUG : Logger::NOTICE;

        if ($debugMode) {
            $this->setFormatter($exceptionFormatter);
        }

        parent::__construct(sprintf('%s/%s', $logPath, $logFileName), $maxFiles, $level);
    }
}
