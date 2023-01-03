<?php

namespace Mautic\ReportBundle\Event;

use Mautic\ReportBundle\Entity\Report;
use Symfony\Contracts\EventDispatcher\Event;

class AbstractReportEvent extends Event
{
    /**
     * @var string
     */
    protected $context = '';

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

    /**
     * Get the context.
     *
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param $context
     *
     * @return bool
     */
    public function checkContext($context)
    {
        if (empty($this->context)) {
            return true;
        }

        if (is_array($context)) {
            $res = array_filter($context, fn ($elem) => 0 === stripos($this->context, $elem));

            return count($res) > 0;
        } elseif ($this->context == $context) {
            return true;
        } elseif (0 === stripos($this->context, $context)) {
            return true;
        } else {
            return false;
        }
    }
}
