<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * SchedulerRepository.
 */
class SchedulerRepository extends EntityRepository
{
    /**
     * @param Report $report
     *
     * @return null|Report
     */
    public function getSchedulerByReport(Report $report)
    {
        return $this->findOneBy(['report' => $report]);
    }
}
