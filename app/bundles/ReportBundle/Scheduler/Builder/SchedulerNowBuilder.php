<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Scheduler\Builder;

use Mautic\ReportBundle\Scheduler\BuilderInterface;
use Mautic\ReportBundle\Scheduler\Exception\InvalidSchedulerException;
use Mautic\ReportBundle\Scheduler\SchedulerInterface;
use Recurr\Exception\InvalidArgument;
use Recurr\Rule;

class SchedulerNowBuilder implements BuilderInterface
{
    /**
     * @throws InvalidSchedulerException
     */
    public function build(Rule $rule, SchedulerInterface $scheduler): Rule
    {
        try {
            $rule->setFreq('SECONDLY');
        } catch (InvalidArgument $e) {
            throw new InvalidSchedulerException();
        }

        return $rule;
    }
}
