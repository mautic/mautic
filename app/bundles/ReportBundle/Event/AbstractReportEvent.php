<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Event;

use Mautic\ReportBundle\Entity\Report;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractReportEvent extends Event
{
    protected ?string $context = null;

    /**
     * Report entity.
     *
     * @var Report
     */
    protected $report;

    /**
     * @return Report
     */
    public function getReport()
    {
        return $this->report;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    /**
     * @return bool
     */
    public function checkContext($context)
    {
        if (empty($this->context)) {
            return true;
        }

        if (is_array($context)) {
            $res = array_filter($context, fn ($elem): bool => 0 === stripos($this->context, (string) $elem));

            return count($res) > 0;
        }
        if ($this->context == $context) {
            return true;
        }

        return 0 === stripos($this->context, (string) $context);
    }
}
